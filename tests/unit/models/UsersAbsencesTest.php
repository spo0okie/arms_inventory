<?php

namespace tests\unit\models;

use app\models\Absences;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Отсутствия на карточке сотрудника: Users::currentAbsences / futureAbsences.
 *
 * Карточка показывает только то, что ещё актуально — идущее сейчас отсутствие
 * и запланированные; прошедшие отсутствия туда не попадают (их полная история
 * есть в разделе «Отсутствия»). Границы периода включительны: день выхода
 * на работу — уже присутствие, а последний день отпуска — ещё отсутствие.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class UsersAbsencesTest extends Unit
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

	private function makeUser(): Users
	{
		$user = new Users();
		$user->setAttributes([
			'Ename' => 'Тестов Тест Тестович',
			'employee_id' => 'absence-test',
		], false);
		$this->assertTrue($user->save(false), print_r($user->errors, true));
		return $user;
	}

	/**
	 * Отсутствие сотрудника со смещением дат в днях относительно сегодня
	 */
	private function makeAbsence(Users $user, string $type, int $fromShift, int $toShift): Absences
	{
		$absence = new Absences();
		$absence->setAttributes([
			'user_id' => $user->id,
			'type' => $type,
			'date_from' => date('Y-m-d', strtotime($fromShift . ' days')),
			'date_to' => date('Y-m-d', strtotime($toShift . ' days')),
			'source' => 'manual',
		], false);
		$this->assertTrue($absence->save(), print_r($absence->errors, true));
		return $absence;
	}

	/**
	 * @param Absences[] $absences
	 * @return int[]
	 */
	private function ids(array $absences): array
	{
		return array_map(static fn(Absences $absence) => (int)$absence->id, array_values($absences));
	}

	public function testCurrentAndFutureAbsencesSplit()
	{
		$user = $this->makeUser();

		$past = $this->makeAbsence($user, 'LEAVESICK', -20, -10);
		$current = $this->makeAbsence($user, 'VACATION', -3, 3);
		$soon = $this->makeAbsence($user, 'ASSIGNMENT', 5, 7);
		$later = $this->makeAbsence($user, 'VACATION_PLAN', 30, 40);

		$this->assertSame([$current->id], $this->ids($user->currentAbsences),
			'сейчас идёт только одно отсутствие');
		$this->assertSame([$soon->id, $later->id], $this->ids($user->futureAbsences),
			'предстоящие идут по календарю, от ближайшего');
		$this->assertSame([$current->id, $soon->id, $later->id], $this->ids($user->pendingAbsences),
			'прошедшее отсутствие на карточку не попадает');
		$this->assertContains($past->id, $this->ids($user->absences),
			'в полном списке прошедшее отсутствие остаётся');
	}

	/**
	 * Границы периода включительны: последний день отпуска — ещё отсутствие,
	 * а вчера закончившееся отсутствие с карточки уходит
	 */
	public function testPeriodBoundsAreInclusive()
	{
		$user = $this->makeUser();

		$endsToday = $this->makeAbsence($user, 'VACATION', -5, 0);
		$startsToday = $this->makeAbsence($user, 'ASSIGNMENT', 0, 2);
		$endedYesterday = $this->makeAbsence($user, 'LEAVESICK', -5, -1);

		$this->assertSame([$endsToday->id, $startsToday->id], $this->ids($user->currentAbsences));
		$this->assertNotContains($endedYesterday->id, $this->ids($user->pendingAbsences));
	}

	/**
	 * Подпись элемента списка: на карточке сотрудника фамилия не повторяется
	 */
	public function testShortNameSkipsEmployee()
	{
		$user = $this->makeUser();
		$absence = $this->makeAbsence($user, 'VACATION', -1, 1);

		$this->assertSame(
			Absences::$types['VACATION'] . ': '
				. date('d.m.Y', strtotime('-1 days')) . ' – ' . date('d.m.Y', strtotime('+1 days')),
			$absence->shortName
		);
		$this->assertStringContainsString($user->sname, $absence->name,
			'полная подпись сотрудника не теряет');
		$this->assertTrue($absence->isCurrent);
	}
}
