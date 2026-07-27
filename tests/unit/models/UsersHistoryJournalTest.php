<?php

namespace tests\unit\models;

use app\models\Users;
use app\models\UsersHistory;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты журнала изменений сотрудников (UsersHistory, таблица users_history).
 *
 * Журнал заведён, чтобы было видно, какие изменения внесены синхронизацией
 * с кадровой БД (SAPsync -> REST PUT): запись создаётся штатным historyCommit
 * из Users::afterSave при реальных изменениях бизнес-полей.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class UsersHistoryJournalTest extends Unit
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
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	private function makeUser(array $attrs = []): Users
	{
		$user = new Users();
		$user->setAttributes(array_merge([
			'Ename' => 'Журналов Тест Историевич',
			'Persg' => 2,
			'Uvolen' => 0,
			'employee_id' => '99990010',
		], $attrs), false);
		$this->assertTrue($user->save(), print_r($user->errors, true));
		//первый UPDATE после INSERT всегда журналирует external_links (NULL -> "[]",
		//см. externalDataBeforeSave — ранний return на insert; так же ведут себя
		//techs/comps). Прогреваем, чтобы тесты мерили только свои изменения.
		$this->assertTrue($user->save(), print_r($user->errors, true));
		return $user;
	}

	private function historyCount(int $masterId): int
	{
		return (int)UsersHistory::find()->where(['master_id' => $masterId])->count();
	}

	/**
	 * Создание сотрудника даёт первую запись журнала,
	 * изменение бизнес-поля — вторую, с корректным changed_attributes.
	 */
	public function testChangesAreJournaled()
	{
		$user = $this->makeUser();
		$count = $this->historyCount($user->id);
		$this->assertGreaterThanOrEqual(1, $count, 'Создание должно журналироваться');

		$user->Doljnost = 'Начальник журнала';
		$this->assertTrue($user->save(), print_r($user->errors, true));

		$this->assertEquals($count + 1, $this->historyCount($user->id), 'Изменение должно журналироваться');

		/** @var UsersHistory $last */
		$last = UsersHistory::find()->where(['master_id' => $user->id])->orderBy(['id' => SORT_DESC])->one();
		$this->assertEquals('Начальник журнала', $last->Doljnost);
		$this->assertContains('Doljnost', explode(',', $last->changed_attributes));
	}

	/**
	 * Сохранение без реальных изменений записи не создаёт.
	 */
	public function testNoChangesNoJournal()
	{
		$user = $this->makeUser(['employee_id' => '99990011']);
		$count = $this->historyCount($user->id);

		$this->assertTrue($user->save(), print_r($user->errors, true));
		$this->assertEquals($count, $this->historyCount($user->id), 'Без изменений запись не нужна');
	}

	/**
	 * Журнальная запись должна уметь называть себя так же, как сотрудник:
	 * снимок из журнала (fetchJournalRecord) рисуется теми же вьюхами
	 * (views/users/item.php -> LinkObjectWidget), что и живой сотрудник.
	 */
	public function testJournalRecordHasName()
	{
		$user = $this->makeUser(['employee_id' => '99990013', 'Ename' => 'Именов Имя Именович']);

		/** @var UsersHistory $last */
		$last = UsersHistory::find()->where(['master_id' => $user->id])->orderBy(['id' => SORT_DESC])->one();
		$this->assertInstanceOf(UsersHistory::class, $last);
		$this->assertEquals($user->name, $last->name);
		$this->assertEquals($user->shortName, $last->shortName);
		$this->assertEquals('Именов И. И.', $last->shortName);
	}

	/**
	 * Секреты не журналируются: их колонок нет в users_history,
	 * смена пароля не оставляет следов в журнале.
	 */
	public function testSecretsAreNotJournaled()
	{
		$history = new UsersHistory();
		foreach (['password', 'auth_key', 'access_token'] as $secret) {
			$this->assertFalse($history->hasAttribute($secret), "users_history не должна содержать $secret");
		}

		$user = $this->makeUser(['employee_id' => '99990012']);
		$count = $this->historyCount($user->id);

		$user->setPassword('new-secret-password');
		$this->assertTrue($user->save(), print_r($user->errors, true));
		$this->assertEquals($count, $this->historyCount($user->id), 'Смена пароля не должна оставлять записей в журнале');
	}
}
