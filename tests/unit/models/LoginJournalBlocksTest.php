<?php

namespace tests\unit\models;

use app\models\Comps;
use app\models\LoginJournal;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Блоки «Входы пользователей» (карточка ОС) и «Входы на ПК» (карточка сотрудника):
 * все открытые сессии, а если их меньше трёх — дополнение последними завершёнными входами.
 * Плюс всё, что вокруг: «состояние неизвестно» у ОС без связи, ручное закрытие,
 * закрытие при архивации ОС, давность входа.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class LoginJournalBlocksTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var int[] id ОС из демо-данных */
	private $comps;
	/** @var int[] id сотрудников из демо-данных */
	private $users;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->comps = array_map('intval', Comps::find()->select('id')->orderBy('id')->limit(4)->column());
		$this->users = array_map('intval', Users::find()->select('id')->orderBy('id')->limit(4)->column());
		$this->assertCount(4, $this->comps, 'в демо-данных нужны хотя бы четыре ОС');
		$this->assertCount(4, $this->users, 'в демо-данных нужны хотя бы четыре сотрудника');
		//в демо-данных у этих ОС и сотрудников есть свои входы — тест начинает с чистого листа
		LoginJournal::deleteAll(['or', ['comps_id' => $this->comps], ['users_id' => $this->users]]);
		//ОС «на связи»: только что обновлялись
		Comps::updateAll(['updated_at' => gmdate('Y-m-d H:i:s')], ['id' => $this->comps]);
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/**
	 * @param int $comp индекс ОС в $this->comps
	 * @param int $user индекс сотрудника в $this->users
	 * @param int $ago  сколько секунд назад был вход
	 * @param array $attrs open=>true — открытая сессия службы; end=>N — сессия, закрытая N секунд назад;
	 *                     без них — запись старого скрипта
	 */
	private function logon(int $comp, int $user, int $ago, array $attrs = []): LoginJournal
	{
		$now = time();
		$rec = new LoginJournal();
		$data = [
			'comp_name' => 'blocks-test',
			'user_login' => 'DOMAIN\\blocks_tester',
			'comps_id' => $this->comps[$comp],
			'users_id' => $this->users[$user],
			'time' => $now - $ago,
			'local_time' => $now,
		];
		if (!empty($attrs['open']) || isset($attrs['end'])) {
			$data['session_uid'] = sprintf('%08x-b10c-4000-8000-%012x', mt_rand(), mt_rand());
		}
		if (isset($attrs['end'])) {
			$data['end_time'] = $now - $attrs['end'];
			$data['end_type'] = LoginJournal::END_LOGOFF;
		}
		$rec->setAttributes($data);
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();
		return $rec;
	}

	private function ids(array $records): array
	{
		return array_map(static function ($rec) { return (int)$rec->id; }, $records);
	}

	/**
	 * Открытых сессий нет — блок выглядит как раньше: три последних входа разных пользователей.
	 */
	public function testCompWithoutOpenSessionsShowsThreeRecentUsers()
	{
		$this->logon(0, 0, 4000);
		$b = $this->logon(0, 1, 3000);
		$this->logon(0, 2, 2500);          //старый вход того же сотрудника — не показывается
		$c = $this->logon(0, 2, 2000);
		$d = $this->logon(0, 3, 1000, ['end' => 500]);

		$this->assertSame($this->ids([$d, $c, $b]), $this->ids(LoginJournal::fetchForComp($this->comps[0])));
	}

	/**
	 * Открытая сессия идёт первой, блок дополняется завершёнными входами других пользователей.
	 * Завершённые входы того, кто работает сейчас, не показываются.
	 */
	public function testCompOpenSessionsFirstThenPaddedWithOthers()
	{
		$old = $this->logon(0, 1, 5000);
		$this->logon(0, 0, 4000, ['end' => 3500]);   //прошлая сессия того, кто работает сейчас
		$open = $this->logon(0, 0, 600, ['open' => true]);
		$recent = $this->logon(0, 2, 2000, ['end' => 1500]);

		$result = LoginJournal::fetchForComp($this->comps[0]);
		$this->assertSame($this->ids([$open, $recent, $old]), $this->ids($result));
		$this->assertTrue($result[0]->isOpen);
	}

	/**
	 * Открытых сессий больше трёх (терминальный сервер) — показываются все, без дополнения.
	 */
	public function testCompShowsAllOpenSessions()
	{
		$this->logon(0, 0, 9000);                    //завершённый — в блок не попадёт
		$open = [];
		foreach ([0, 1, 2, 3] as $i) $open[] = $this->logon(0, $i, 1000 - $i * 100, ['open' => true]);

		$result = LoginJournal::fetchForComp($this->comps[0]);
		$this->assertCount(4, $result);
		foreach ($result as $rec) $this->assertTrue($rec->isOpen);
		//свежие — выше
		$this->assertSame((int)$open[3]->id, (int)$result[0]->id);
	}

	public function testUserBlockMirrorsCompBlock()
	{
		$old = $this->logon(1, 0, 5000);
		$this->logon(0, 0, 4000, ['end' => 3500]);   //прошлая сессия на ОС, где он работает сейчас
		$open = $this->logon(0, 0, 600, ['open' => true]);
		$recent = $this->logon(2, 0, 2000, ['end' => 1500]);
		$this->logon(3, 1, 100, ['open' => true]);   //чужая сессия

		$this->assertSame(
			$this->ids([$open, $recent, $old]),
			$this->ids(LoginJournal::fetchForUser($this->users[0]))
		);
		$this->assertSame($this->ids([$open, $recent, $old]), $this->ids($this->userModel(0)->lastThreeLogins));
	}

	private function userModel(int $i): Users
	{
		return Users::findOne($this->users[$i]);
	}

	/**
	 * Открытая сессия на ОС, которая на связи, — «сейчас»; на ОС, которая молчит, — «неизвестно».
	 */
	public function testStaleDependsOnHostSilence()
	{
		$rec = $this->logon(0, 0, 600, ['open' => true]);
		$this->assertFalse($rec->isStale);

		Comps::updateAll(
			['updated_at' => gmdate('Y-m-d H:i:s', time() - LoginJournal::$silentAfter - 60)],
			['id' => $this->comps[0]]
		);
		$rec = LoginJournal::findOne($rec->id);
		$this->assertTrue($rec->isStale);

		//завершённый вход и запись старого скрипта «неизвестными» не бывают
		$this->assertFalse($this->logon(0, 1, 600, ['end' => 100])->isStale);
		$this->assertFalse($this->logon(0, 2, 600)->isStale);
	}

	public function testManualCloseOnlyForStaleSession()
	{
		$rec = $this->logon(0, 0, 600, ['open' => true]);
		//ОС на связи — она сообщит о сессии сама, вручную закрывать нельзя
		$this->assertFalse($rec->closeManually('admin'));
		$this->assertTrue(LoginJournal::findOne($rec->id)->isOpen);

		Comps::updateAll(['updated_at' => '2020-01-01 00:00:00'], ['id' => $this->comps[0]]);
		$rec = LoginJournal::findOne($rec->id);
		$this->assertTrue($rec->closeManually('admin'));

		$rec = LoginJournal::findOne($rec->id);
		$this->assertFalse($rec->isOpen);
		$this->assertSame(LoginJournal::END_MANUAL, (int)$rec->end_type);
		$this->assertSame('MANUAL', $rec->endTypeName);
		$this->assertSame('admin', $rec->closed_by);
		$this->assertEqualsWithDelta(time(), LoginJournal::utcToTime($rec->end_time), 5);
		//повторно закрывать нечего
		$this->assertFalse($rec->closeManually('admin'));
	}

	/**
	 * ОС ушла в архив — её открытые сессии закрываются: сама она о них уже не сообщит.
	 */
	public function testArchivingCompClosesItsSessions()
	{
		$mine = $this->logon(0, 0, 600, ['open' => true]);
		$other = $this->logon(1, 1, 600, ['open' => true]);

		$comp = Comps::findOne($this->comps[0]);
		$comp->archived = 1;
		$this->assertTrue($comp->save(), print_r($comp->errors, true));

		$mine = LoginJournal::findOne($mine->id);
		$this->assertFalse($mine->isOpen);
		$this->assertSame(LoginJournal::END_LOST, (int)$mine->end_type);
		$this->assertTrue(LoginJournal::findOne($other->id)->isOpen);
	}

	/**
	 * Давность входа считается от времени в UTC, как оно лежит в БД, — независимо от
	 * часового пояса приложения.
	 */
	public function testAgeIsTimezoneIndependent()
	{
		$this->assertSame('10мин', $this->logon(0, 0, 630)->age);
		$this->assertSame('3ч', $this->logon(0, 1, 3 * 3600 + 5)->age);
		$this->assertSame('45сек', LoginJournal::ageText(45));
		$this->assertSame('2д', LoginJournal::ageText(2 * 86400 + 10));
	}

	public function testFlagsDescr()
	{
		$rec = new LoginJournal();
		$rec->flags = LoginJournal::FLAG_START_ESTIMATED | LoginJournal::FLAG_TIME_UNCONFIRMED;
		$this->assertSame(['начало — оценка', 'время не подтверждено'], $rec->flagsDescr);
		$rec->flags = 0;
		$this->assertSame([], $rec->flagsDescr);
	}
}
