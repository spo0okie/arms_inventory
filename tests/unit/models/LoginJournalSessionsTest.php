<?php

namespace tests\unit\models;

use app\models\LoginJournal;
use Codeception\Test\Unit;
use Yii;

/**
 * Сессии в журнале входов (LoginJournal с session_uid): окончание, флаги,
 * правила повторной доставки и закрытие потерянных сессий.
 *
 * Контракт — arms.winservice/TZ.md §7: служба инвентаризации Windows шлёт
 * состояние сессии, сервер делает upsert по ключу. Здесь проверяется модель;
 * HTTP-слой — сценарии testPush() контроллеров api/login-journal и api/comps.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class LoginJournalSessionsTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var int[] id двух ОС из демо-данных: comps_id проверяется на существование */
	private $comps;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->comps = array_map('intval', \app\models\Comps::find()->select('id')->orderBy('id')->limit(2)->column());
		$this->assertCount(2, $this->comps, 'в демо-данных нужны хотя бы две ОС');
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
	}

	/**
	 * Запись сессии так, как её создаёт REST create: атрибуты в формате API.
	 */
	private function push(array $attrs = []): LoginJournal
	{
		$now = time();
		$rec = new LoginJournal();
		$rec->setAttributes(array_merge([
			'session_uid' => $this->uid(),
			'comp_name' => 'sess-test.domain.local',
			'user_login' => 'DOMAIN\\sess_tester',
			'type' => 0,
			'time' => $now - 600,
			'local_time' => $now,
		], $attrs));
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();
		return $rec;
	}

	private function uid(): string
	{
		return sprintf('%08x-0000-4000-8000-%012x', mt_rand(), mt_rand());
	}

	private function ts(?string $dbTime): ?int
	{
		return $dbTime === null ? null : strtotime($dbTime . ' UTC');
	}

	public function testOpenSessionIsOpen()
	{
		$rec = $this->push();
		$this->assertTrue($rec->isOpen);
		$this->assertNull($rec->end_time);
		$this->assertSame(0, (int)$rec->flags);
		$this->assertContains($rec->id, LoginJournal::findOpen()->select('id')->column());
	}

	/**
	 * Запись старого скрипта (без ключа сессии) открытой не считается:
	 * иначе вся история журнала выглядела бы как незакрытые сессии.
	 */
	public function testLegacyRecordIsNotOpen()
	{
		$rec = $this->push(['session_uid' => null]);
		$this->assertFalse($rec->isOpen);
		$this->assertNotContains($rec->id, LoginJournal::findOpen()->select('id')->column());
	}

	public function testSessionUidIsUnique()
	{
		$first = $this->push();
		$dup = new LoginJournal();
		$dup->setAttributes([
			'session_uid' => $first->session_uid,
			'comp_name' => 'sess-test.domain.local',
			'user_login' => 'DOMAIN\\sess_tester',
			'time' => time() - 60,
		]);
		$this->assertFalse($dup->save());
		$this->assertTrue($dup->hasErrors('session_uid'));
	}

	/**
	 * Часы клиента в норме — начало и окончание пишутся как присланы.
	 */
	public function testEndTimeStoredWithoutShiftWhenClockIsFine()
	{
		$now = time();
		$rec = $this->push(['time' => $now - 600, 'end_time' => $now - 60, 'end_type' => LoginJournal::END_LOGOFF]);
		$this->assertFalse($rec->isOpen);
		$this->assertSame($now - 600, $this->ts($rec->calc_time));
		$this->assertSame($now - 60, $this->ts($rec->end_time));
		$this->assertSame(LoginJournal::END_LOGOFF, (int)$rec->end_type);
	}

	/**
	 * Часы клиента сбиты (2000 год): и начало, и окончание сдвигаются на расхождение
	 * часов клиента и сервера.
	 */
	public function testStartAndEndShiftedWhenClientClockIsWrong()
	{
		$now = time();
		$clientNow = gmmktime(0, 10, 0, 1, 1, 2000);	//у клиента 2000-01-01 00:10
		$rec = $this->push([
			'time' => $clientNow - 600,
			'end_time' => $clientNow - 60,
			'end_type' => LoginJournal::END_LOGOFF,
			'local_time' => $clientNow,
		]);
		$this->assertEqualsWithDelta($now - 600, $this->ts($rec->calc_time), 2);
		$this->assertEqualsWithDelta($now - 60, $this->ts($rec->end_time), 2);
		//оригинальное время клиента сохраняется как есть
		$this->assertSame($clientNow - 600, $this->ts($rec->time));
	}

	public function testEndInFutureIsRejected()
	{
		$now = time();
		$rec = new LoginJournal();
		$rec->setAttributes([
			'session_uid' => $this->uid(),
			'comp_name' => 'sess-test.domain.local',
			'user_login' => 'DOMAIN\\sess_tester',
			'time' => $now - 600,
			'end_time' => $now + 3600,
			'local_time' => $now,
		]);
		$this->assertFalse($rec->save());
		$this->assertTrue($rec->hasErrors('end_time'));
	}

	/**
	 * Повторная доставка открытой сессии ничего не меняет: начало пишется один раз,
	 * даже если во второй доставке оно другое (плавает на погрешность доставки).
	 */
	public function testRepeatedOpenPushChangesNothing()
	{
		$rec = $this->push();
		$calc = $rec->calc_time;
		$changed = $rec->applySessionPush([
			'session_uid' => $rec->session_uid,
			'time' => time() - 590,
			'local_time' => time(),
		]);
		$this->assertFalse($changed);
		$this->assertSame($calc, $rec->calc_time);
		$this->assertTrue($rec->isOpen);
	}

	public function testClosePushEndsSession()
	{
		$now = time();
		$rec = $this->push(['flags' => LoginJournal::FLAG_START_ESTIMATED]);
		$calc = $rec->calc_time;

		$this->assertTrue($rec->applySessionPush([
			'end_time' => $now - 30,
			'end_type' => LoginJournal::END_SYSTEM_STOP,
			'flags' => LoginJournal::FLAG_END_ESTIMATED,
			'local_time' => $now,
			'time' => $now - 1,	//присланное начало игнорируется
		]));
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();

		$this->assertFalse($rec->isOpen);
		$this->assertSame($now - 30, $this->ts($rec->end_time));
		$this->assertSame(LoginJournal::END_SYSTEM_STOP, (int)$rec->end_type);
		$this->assertSame($calc, $rec->calc_time, 'начало не должно меняться при закрытии');
		//флаг начала — от первой записи, флаг окончания — от закрытия
		$this->assertSame(
			LoginJournal::FLAG_START_ESTIMATED | LoginJournal::FLAG_END_ESTIMATED,
			(int)$rec->flags
		);
	}

	/**
	 * Окончание присылается в часах клиента — при сбитых часах сдвигается
	 * так же, как при создании записи.
	 */
	public function testClosePushShiftsEndWhenClientClockIsWrong()
	{
		$now = time();
		$clientNow = gmmktime(0, 10, 0, 1, 1, 2000);
		$rec = $this->push();

		$this->assertTrue($rec->applySessionPush([
			'end_time' => $clientNow - 45,
			'end_type' => LoginJournal::END_LOGOFF,
			'local_time' => $clientNow,
		]));
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();
		$this->assertEqualsWithDelta($now - 45, $this->ts($rec->end_time), 2);
	}

	public function testRepeatedClosePushChangesNothing()
	{
		$now = time();
		$rec = $this->push(['end_time' => $now - 60, 'end_type' => LoginJournal::END_LOGOFF]);
		$end = $rec->end_time;

		$this->assertFalse($rec->applySessionPush([
			'end_time' => $now - 10,
			'end_type' => LoginJournal::END_SHUTDOWN,
			'local_time' => $now,
		]));
		$this->assertSame($end, $rec->end_time);
		$this->assertSame(LoginJournal::END_LOGOFF, (int)$rec->end_type);
	}

	/**
	 * Запоздавшее «сессия открыта» не открывает сессию, закрытие которой сообщила служба.
	 */
	public function testOpenPushDoesNotReopenServiceClosedSession()
	{
		$now = time();
		$rec = $this->push(['end_time' => $now - 60, 'end_type' => LoginJournal::END_LOGOFF]);
		$this->assertFalse($rec->applySessionPush(['local_time' => $now]));
		$this->assertFalse($rec->isOpen);
	}

	/**
	 * Окончание, поставленное сервером, — догадка: реальное окончание от службы его перезаписывает.
	 * @dataProvider serverEndTypes
	 */
	public function testServiceEndOverwritesServerEnd(int $serverType)
	{
		$now = time();
		$rec = $this->push();
		LoginJournal::updateAll(
			['end_time' => gmdate('Y-m-d H:i:s', $now - 5), 'end_type' => $serverType, 'closed_by' => 'admin'],
			['id' => $rec->id]
		);
		$rec->refresh();
		$this->assertFalse($rec->isOpen);

		$this->assertTrue($rec->applySessionPush([
			'end_time' => $now - 300,
			'end_type' => LoginJournal::END_LOGOFF,
			'local_time' => $now,
		]));
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();
		$this->assertSame($now - 300, $this->ts($rec->end_time));
		$this->assertSame(LoginJournal::END_LOGOFF, (int)$rec->end_type);
		$this->assertNull($rec->closed_by);
	}

	/**
	 * Хост вернулся и сообщает, что сессия, закрытая сервером, на самом деле открыта.
	 * @dataProvider serverEndTypes
	 */
	public function testOpenPushReopensServerClosedSession(int $serverType)
	{
		$rec = $this->push();
		LoginJournal::updateAll(
			['end_time' => gmdate('Y-m-d H:i:s'), 'end_type' => $serverType, 'closed_by' => 'admin'],
			['id' => $rec->id]
		);
		$rec->refresh();

		$this->assertTrue($rec->applySessionPush(['local_time' => time()]));
		$this->assertTrue($rec->save(), print_r($rec->errors, true));
		$rec->refresh();
		$this->assertTrue($rec->isOpen);
		$this->assertNull($rec->end_type);
		$this->assertNull($rec->closed_by);
	}

	/**
	 * Числами, а не константами модели: провайдер вызывается до подключения автозагрузчика приложения.
	 */
	public function serverEndTypes(): array
	{
		return [
			'LOST' => [100],
			'MANUAL' => [101],
		];
	}

	public function testServerEndTypeCodes()
	{
		$this->assertSame([100, 101], LoginJournal::SERVER_END_TYPES);
	}

	/**
	 * Полный список открытых сессий хоста закрывает те, которых в нём нет,
	 * и не трогает: сессии из списка, уже закрытые, чужие и записи старых скриптов.
	 */
	public function testCloseLost()
	{
		$now = time();
		$kept = $this->push(['comps_id' => $this->comps[0]]);
		$lost = $this->push(['comps_id' => $this->comps[0]]);
		$closed = $this->push(['comps_id' => $this->comps[0], 'end_time' => $now - 60, 'end_type' => LoginJournal::END_LOGOFF]);
		$other = $this->push(['comps_id' => $this->comps[1]]);
		$legacy = $this->push(['comps_id' => $this->comps[0], 'session_uid' => null]);

		$this->assertSame(1, LoginJournal::closeLost($this->comps[0], [$kept->session_uid]));

		foreach ([$kept, $lost, $closed, $other, $legacy] as $rec) $rec->refresh();
		$this->assertTrue($kept->isOpen);
		$this->assertFalse($lost->isOpen);
		$this->assertSame(LoginJournal::END_LOST, (int)$lost->end_type);
		$this->assertEqualsWithDelta($now, $this->ts($lost->end_time), 5);
		$this->assertSame(LoginJournal::END_LOGOFF, (int)$closed->end_type);
		$this->assertTrue($other->isOpen);
		$this->assertNull($legacy->end_time);
	}

	public function testCloseLostWithEmptyListClosesAll()
	{
		$a = $this->push(['comps_id' => $this->comps[0]]);
		$b = $this->push(['comps_id' => $this->comps[0]]);
		$this->assertSame(2, LoginJournal::closeLost($this->comps[0], []));
		$a->refresh();
		$b->refresh();
		$this->assertFalse($a->isOpen);
		$this->assertFalse($b->isOpen);
	}
}
