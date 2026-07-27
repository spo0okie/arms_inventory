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

	/** @var mixed исходное значение user.name_as_uid.enable (тесты его переключают) */
	private $nameAsUid;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->nameAsUid = Yii::$app->params['user.name_as_uid.enable'] ?? false;
	}

	protected function _after()
	{
		Yii::$app->params['user.name_as_uid.enable'] = $this->nameAsUid;
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

	/**
	 * Человек без ИНН: при включенном user.name_as_uid.enable ключом становится полное ФИО,
	 * и слияние вокруг логина должно работать так же, как по uid.
	 * До фикса afterSave выходил на первой же строке (if (!$this->uid) return), и разрешение
	 * опознавать человека по ФИО не работало вообще.
	 */
	public function testNameAsUidMergesRecordsWithoutUid()
	{
		Yii::$app->params['user.name_as_uid.enable'] = true;
		$login = 'dup.merge.nouid';
		$name = 'Безынновый Сотрудник Тестович';

		$dismissed = $this->makeUser([
			'employee_id' => '99990005',
			'Ename' => $name,
			'uid' => '',
			'Login' => $login,
			'Uvolen' => 1,
		]);

		$journal = new LoginJournal();
		$journal->setAttributes(['users_id' => $dismissed->id, 'comp_name' => 'dup-test-comp', 'user_login' => $login], false);
		$this->assertTrue($journal->save(false));

		$active = $this->makeUser([
			'employee_id' => '99990006',
			'Ename' => $name,
			'uid' => '',
			'Login' => $login,
		]);

		$journal->refresh();
		$this->assertEquals($active->id, $journal->users_id);
		$dismissed->refresh();
		$this->assertEquals('', $dismissed->Login);
	}

	/**
	 * Тот же расклад, но опознавание по ФИО запрещено: опознать человека не по чему,
	 * слияния быть не должно (иначе пустой uid стал бы ключом и сливал всех без ИНН).
	 *
	 * Штатно такую пару даже не создать - валидатор уникальности логина не пропустит
	 * (см. rules(): дубль логина разрешен только по совпадению uid либо ФИО при
	 * user.name_as_uid.enable), поэтому состояние собирается через save(false):
	 * проверяем именно afterSave, а не валидацию.
	 */
	public function testWithoutUidAndNameAsUidNoMerge()
	{
		Yii::$app->params['user.name_as_uid.enable'] = false;
		$login = 'dup.merge.nokey';
		$name = 'Неопознаваемый Сотрудник Тестович';

		$dismissed = $this->makeUser([
			'employee_id' => '99990007',
			'Ename' => $name,
			'uid' => '',
			'Login' => $login,
			'Uvolen' => 1,
		]);

		$journal = new LoginJournal();
		$journal->setAttributes(['users_id' => $dismissed->id, 'comp_name' => 'dup-test-comp', 'user_login' => $login], false);
		$this->assertTrue($journal->save(false));

		$active = $this->makeUser([
			'employee_id' => '99990008',
			'Ename' => $name,
			'uid' => '',
			'Login' => 'dup.merge.nokey.other',
		]);

		//тот же логин, что у уволенной записи - в обход валидатора, но с вызовом afterSave
		$active->Login = $login;
		$this->assertTrue($active->save(false));

		//слияния не было: журнал и логин остались на прежней записи
		$journal->refresh();
		$this->assertEquals($dismissed->id, $journal->users_id);
		$dismissed->refresh();
		$this->assertEquals($login, $dismissed->Login);
	}
}
