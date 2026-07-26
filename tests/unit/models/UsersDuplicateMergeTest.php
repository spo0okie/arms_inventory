<?php

namespace tests\unit\models;

use app\models\LoginJournal;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты слияния дублей в Users::afterSave (absorbUser).
 *
 * Регрессия: PUT /api/users/{id} из SAPsync зависал до max_execution_time.
 * Причина — у одного человека несколько записей-трудоустройств (разные
 * employee_id, один uid=md5(СНИЛС)) с ПУСТЫМИ логинами: '' совпадал с '',
 * afterSave находил «дубля», absorbUser не менял жертву (отбирать нечего,
 * uid/Ename/Login оставались прежними), $this->save() внутри absorbUser
 * снова звал afterSave — бесконечная рекурсия из одних SELECT'ов.
 *
 * Фикс двойной: (1) пустой Login не считается ключом слияния;
 * (2) флаг absorbing рвёт рекурсию afterSave->absorbUser->save->afterSave.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class UsersDuplicateMergeTest extends Unit
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

	/**
	 * Создает сотрудника с нужными полями (минимально валидный набор)
	 */
	private function makeUser(array $attrs): Users
	{
		$user = new Users();
		$user->setAttributes(array_merge([
			'Ename' => 'Тестовый Сотрудник Дубльевич',
			'Persg' => 2,
			'Uvolen' => 0,
		], $attrs), false);
		$this->assertTrue($user->save(), print_r($user->errors, true));
		return $user;
	}

	/**
	 * Два трудоустройства одного человека (один uid, разные employee_id),
	 * логины пустые. Слияние НЕ должно запускаться: пустой логин - не ключ.
	 * До фикса этот сценарий уходил в бесконечную рекурсию (тест бы завис).
	 */
	public function testEmptyLoginDoesNotTriggerMerge()
	{
		$uid = md5('duplicate-merge-test-1');

		$dismissed = $this->makeUser([
			'employee_id' => '99990001',
			'uid' => $uid,
			'Login' => '',
			'Uvolen' => 1,
		]);

		//журнальная запись на "жертве" - маркер: слияние перевесило бы её на выжившего
		$journal = new LoginJournal();
		$journal->setAttributes(['users_id' => $dismissed->id, 'comp_name' => 'dup-test-comp', 'user_login' => 'dup.merge.test'], false);
		$this->assertTrue($journal->save(false));

		$active = $this->makeUser([
			'employee_id' => '99990002',
			'uid' => $uid,
			'Login' => '',
		]);

		//повторное сохранение активного - ровно то, что делает PUT из SAPsync
		$active->Doljnost = 'Обновленная должность';
		$this->assertTrue($active->save(), print_r($active->errors, true));

		//жертва цела: uid на месте, журнал не перевешан - слияния не было
		$dismissed->refresh();
		$this->assertEquals($uid, $dismissed->uid);
		$journal->refresh();
		$this->assertEquals($dismissed->id, $journal->users_id);
	}

	/**
	 * Штатное слияние вокруг реального логина (повторный прием на работу)
	 * должно работать как раньше: журнал переезжает на выжившего,
	 * логин у жертвы очищается, процесс завершается (не зацикливается).
	 */
	public function testRealLoginMergeStillWorks()
	{
		$uid = md5('duplicate-merge-test-2');
		$login = 'dup.merge.test';

		$dismissed = $this->makeUser([
			'employee_id' => '99990003',
			'uid' => $uid,
			'Login' => $login,
			'Uvolen' => 1,
		]);

		$journal = new LoginJournal();
		$journal->setAttributes(['users_id' => $dismissed->id, 'comp_name' => 'dup-test-comp', 'user_login' => 'dup.merge.test'], false);
		$this->assertTrue($journal->save(false));

		$active = $this->makeUser([
			'employee_id' => '99990004',
			'uid' => $uid,
			'Login' => $login,
		]);

		//слияние произошло: журнал переехал на выжившего, у жертвы очищен логин
		$journal->refresh();
		$this->assertEquals($active->id, $journal->users_id);
		$dismissed->refresh();
		$this->assertEquals('', $dismissed->Login);
	}
}
