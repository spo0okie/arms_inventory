<?php

namespace tests\unit\models;

use app\models\AccessTypes;
use app\models\Aces;
use app\models\Acls;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты очистки junction-таблиц при удалении моделей с many-to-many связями.
 *
 * LinkerBehavior пишет junction-таблицы только на insert/update, FK на junction
 * в проекте нет (M251221163631ClearFk) — до фикса $model->delete() оставлял
 * осиротевшие строки в users_in_aces/access_in_aces и т.п. (веб-удаление через
 * ArmsBaseController::actionDelete зовет тот же delete(), так что путь один).
 * Фикс: ArmsModel::afterDelete -> AttributeLinksModelTrait::deleteJunctionRows.
 *
 * Данные оборачиваются в транзакцию и откатываются (unit-suite без cleanup).
 */
class AcesDeleteJunctionTest extends Unit
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

	private function makeAcl(): Acls
	{
		$acl = new Acls();
		$acl->setAttributes([
			'comment' => 'ACL для теста junction-очистки',
		], false);
		$this->assertTrue($acl->save(false), print_r($acl->errors, true));
		return $acl;
	}

	private function makeAccessType(): AccessTypes
	{
		$type = new AccessTypes();
		$type->setAttributes([
			'name' => 'junction-test-access',
			'comment' => 'тип доступа для теста junction-очистки',
		], false);
		$this->assertTrue($type->save(false), print_r($type->errors, true));
		return $type;
	}

	private function makeUser(): Users
	{
		$user = new Users();
		$user->setAttributes([
			'Ename' => 'Тестовый Сотрудник Джанкшенович',
			'Persg' => 2,
			'Uvolen' => 0,
		], false);
		$this->assertTrue($user->save(false), print_r($user->errors, true));
		return $user;
	}

	private function makeAce(Acls $acl, Users $user, AccessTypes $type): Aces
	{
		$ace = new Aces();
		$ace->setAttributes([
			'acls_id' => $acl->id,
			'comment' => 'ACE для теста junction-очистки',
		], false);
		$ace->users_ids = [$user->id];
		$ace->access_types_ids = [$type->id];
		$this->assertTrue($ace->save(false), print_r($ace->errors, true));
		$ace->refresh();
		return $ace;
	}

	private function junctionCount(string $table, string $column, int $id): int
	{
		return (int)(new \yii\db\Query())->from($table)->where([$column => $id])->count();
	}

	/**
	 * Удаление ACE должно удалять его строки из junction-таблиц
	 * (до фикса они оставались навсегда: FK нет, LinkerBehavior на delete молчит).
	 */
	public function testDeleteAceCleansJunctionRows()
	{
		$ace = $this->makeAce($this->makeAcl(), $this->makeUser(), $this->makeAccessType());

		$this->assertSame(1, $this->junctionCount('users_in_aces', 'aces_id', $ace->id),
			'связь с пользователем должна попасть в junction при сохранении');
		$this->assertSame(1, $this->junctionCount('access_in_aces', 'aces_id', $ace->id),
			'связь с типом доступа должна попасть в junction при сохранении');

		$this->assertNotFalse($ace->delete());

		$this->assertSame(0, $this->junctionCount('users_in_aces', 'aces_id', $ace->id),
			'после удаления ACE в users_in_aces не должно остаться его строк');
		$this->assertSame(0, $this->junctionCount('access_in_aces', 'aces_id', $ace->id),
			'после удаления ACE в access_in_aces не должно остаться его строк');
	}

	/**
	 * Очистка должна затрагивать только удаляемый ACE:
	 * junction-строки соседнего ACE с теми же субъектами обязаны выжить.
	 */
	public function testDeleteAceKeepsOtherAcesRows()
	{
		$acl = $this->makeAcl();
		$user = $this->makeUser();
		$type = $this->makeAccessType();
		$victim = $this->makeAce($acl, $user, $type);
		$survivor = $this->makeAce($acl, $user, $type);

		$this->assertNotFalse($victim->delete());

		$this->assertSame(1, $this->junctionCount('users_in_aces', 'aces_id', $survivor->id),
			'junction-строки соседнего ACE не должны пострадать');
		$this->assertSame(1, $this->junctionCount('access_in_aces', 'aces_id', $survivor->id),
			'junction-строки соседнего ACE не должны пострадать');
	}

	/**
	 * Обратная сторона: удаление пользователя должно чистить users_in_aces
	 * по users_id (у Users в linksSchema aces_ids — та же junction, другой ключ).
	 */
	public function testDeleteUserCleansJunctionRows()
	{
		$user = $this->makeUser();
		$ace = $this->makeAce($this->makeAcl(), $user, $this->makeAccessType());

		$this->assertSame(1, $this->junctionCount('users_in_aces', 'users_id', $user->id));

		$this->assertNotFalse($user->delete());

		$this->assertSame(0, $this->junctionCount('users_in_aces', 'users_id', $user->id),
			'после удаления пользователя в users_in_aces не должно остаться его строк');
		//сам ACE при этом жив, просто субъект отвязан
		$this->assertNotNull(Aces::findOne($ace->id));
	}
}
