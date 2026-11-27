<?php

namespace tests\unit\models;

use app\models\Users;
use app\models\UsersHistory;
use app\models\UsersSearch;
use Codeception\Test\Unit;
use Yii;

/**
 * Откладывание увольнения сотрудника (resign_defer / resign_defer_until)
 * и итоговый признак resigned = Uvolen за вычетом действующего откладывания.
 *
 * Главное, что здесь сторожится, - совпадение PHP-расчёта (Users::$resigned)
 * и его SQL-двойника (Users::resignedExpression()), на котором стоят выборки.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class UsersResignDeferTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		Users::$working_cache = null;
	}

	protected function _after()
	{
		Users::$working_cache = null;
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function makeUser(array $attrs = []): Users
	{
		$user = new Users();
		$user->setAttributes(array_merge([
			'Ename' => 'Отложенный Тест Увольнениевич',
			'Persg' => 2,
			'Uvolen' => 0,
			'employee_id' => '99990020',
		], $attrs), false);
		$this->assertTrue($user->save(), print_r($user->errors, true));
		return $user;
	}

	/**
	 * Значение SQL-двойника resigned для записи
	 */
	private function sqlResigned(Users $user): bool
	{
		return (bool)Users::find()
			->select(Users::resignedExpression())
			->where(['id' => $user->id])
			->scalar();
	}

	/**
	 * Все сочетания Uvolen/откладывания: [атрибуты, ожидаемый resigned, относительный срок]
	 */
	public function casesProvider(): array
	{
		return [
			'работает' => [['Uvolen' => 0], false],
			'работает, откладывание выставлено заранее' => [['Uvolen' => 0, 'resign_defer' => 1], false],
			'уволен' => [['Uvolen' => 1], true],
			'уволен, отложено бессрочно' => [['Uvolen' => 1, 'resign_defer' => 1], false],
			'уволен, отложено до завтра' => [['Uvolen' => 1, 'resign_defer' => 1], false, 'tomorrow'],
			'уволен, отложено до сегодня (включительно)' => [['Uvolen' => 1, 'resign_defer' => 1], false, 'today'],
			'уволен, срок откладывания истёк' => [['Uvolen' => 1, 'resign_defer' => 1], true, 'yesterday'],
		];
	}

	/**
	 * PHP-расчёт и SQL-выражение дают одно и то же, архивность следует за resigned
	 * @dataProvider casesProvider
	 */
	public function testResignedPhpMatchesSql(array $attrs, bool $expected, ?string $until = null)
	{
		// Провайдер вызывается раньше настройки часового пояса приложения.
		if ($until !== null) $attrs['resign_defer_until'] = (new \DateTimeImmutable($until))->format('Y-m-d');
		$user = $this->makeUser($attrs);
		$user->refresh();

		$this->assertSame($expected, (bool)$user->resigned, 'PHP resigned');
		$this->assertSame($expected, $this->sqlResigned($user), 'SQL resigned');
		$this->assertSame($expected, (bool)$user->isArchived, 'архивность = resigned');
		$this->assertSame($expected, $user->toArray()['resigned'], 'resigned отдаётся в REST');
	}

	/** PHP и SQL используют дату приложения, даже когда в соединении с БД другой день. */
	public function testResignedUsesApplicationDateWhenDatabaseDateDiffers(): void
	{
		$databaseDate = Yii::$app->db->createCommand('SELECT CURDATE()')->queryScalar();
		$previousZone = date_default_timezone_get();
		try {
			// В поясах UTC+14 и UTC−12 не может быть одна и та же календарная дата.
			date_default_timezone_set('Pacific/Kiritimati');
			if (date('Y-m-d') === $databaseDate) date_default_timezone_set('Etc/GMT+12');
			$today = date('Y-m-d');
			$this->assertNotSame($databaseDate, $today);
			$until = min($today, $databaseDate);
			$user = $this->makeUser(['Uvolen'=>1, 'resign_defer'=>1, 'resign_defer_until'=>$until]);
			$this->assertSame($until < $today, $user->resigned);
			$this->assertSame($user->resigned, $this->sqlResigned($user));
		} finally {
			date_default_timezone_set($previousZone);
		}
	}

	/**
	 * Срок без включённого откладывания не хранится
	 */
	public function testUntilClearedWithoutDefer()
	{
		$user = $this->makeUser(['Uvolen' => 1, 'resign_defer' => 0, 'resign_defer_until' => date('Y-m-d', strtotime('+1 month'))]);
		$user->refresh();
		$this->assertNull($user->resign_defer_until);
		$this->assertTrue((bool)$user->resigned);
	}

	/**
	 * Срок - только дата ГГГГ-ММ-ДД (сравнивается строкой и в PHP, и в SQL)
	 */
	public function testUntilFormatValidated()
	{
		$user = new Users(['Ename' => 'Формат Даты', 'Persg' => 1, 'Uvolen' => 1, 'resign_defer' => 1]);
		$user->resign_defer_until = '31.12.2030';
		$this->assertFalse($user->validate(['resign_defer_until']));
		$user->resign_defer_until = '2030-12-31';
		$this->assertTrue($user->validate(['resign_defer_until']), print_r($user->errors, true));
		$user->resign_defer_until = '';
		$this->assertTrue($user->validate(['resign_defer_until']), print_r($user->errors, true));
		$this->assertNull($user->resign_defer_until, 'пустой срок = бессрочно (NULL)');
	}

	/**
	 * Сотрудник с отложенным увольнением остаётся в списках работающих,
	 * уволенный без откладывания - нет
	 */
	public function testListsFollowResigned()
	{
		$deferred = $this->makeUser(['Uvolen' => 1, 'resign_defer' => 1, 'Login' => 'resign-defer-test-1']);
		$resigned = $this->makeUser(['Uvolen' => 1, 'Login' => 'resign-defer-test-2', 'employee_id' => '99990021']);

		$working = Users::fetchWorking();
		$this->assertArrayHasKey($deferred->id, $working);
		$this->assertArrayNotHasKey($resigned->id, $working);

		$names = Users::listItems();
		$this->assertArrayHasKey($deferred->id, $names);
		$this->assertArrayNotHasKey($resigned->id, $names);

		//список сотрудников без архивных
		$search = new UsersSearch();
		$search->archived = false;
		$ids = array_map('intval', $search->search([])->query->select('users.id')->column());
		$this->assertContains((int)$deferred->id, $ids);
		$this->assertNotContains((int)$resigned->id, $ids);
	}

	/**
	 * Поля откладывания журналируются, а журнальная запись умеет считать resigned
	 * (по нему ссылка на сотрудника в карточках изменений рисуется зачёркнутой)
	 */
	public function testHistoryMirrorsDefer()
	{
		$user = $this->makeUser(['Uvolen' => 1]);
		$user->resign_defer = 1;
		$this->assertTrue($user->save(), print_r($user->errors, true));

		/** @var UsersHistory $last */
		$last = UsersHistory::find()->where(['master_id' => $user->id])->orderBy(['id' => SORT_DESC])->one();
		$this->assertNotNull($last);
		$this->assertEquals(1, $last->resign_defer);
		$this->assertStringContainsString('resign_defer', (string)$last->changed_attributes);
		$this->assertFalse((bool)$last->resigned);
	}
}
