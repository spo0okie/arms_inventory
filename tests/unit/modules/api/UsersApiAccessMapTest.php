<?php

namespace tests\unit\modules\api;

use app\controllers\ArmsBaseController;
use app\modules\api\controllers\UsersController;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты карты доступа REST-контроллера пользователей.
 *
 * Регрессия: POST /api/users/migrate отдавал 403 на боевой установке, а локально проходил.
 * Причина — action был объявлен только под точечным правом update-users, которого у учетки
 * синхронизации нет: у нее глобальное edit (роль editor). Локально это не ловилось, т.к.
 * при useRBAC=false правила доступа вообще не навешиваются (см. BaseRestController::behaviors).
 *
 * Поэтому проверяем саму карту: каждое меняющее данные действие должно быть доступно
 * и по глобальному edit, и по точечному update-users.
 */
class UsersApiAccessMapTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	private function accessMap(): array
	{
		$controller=new UsersController('users', Yii::$app->getModule('api'));
		return $controller->accessMap();
	}

	/**
	 * Действия, меняющие данные, должны пускаться по глобальному праву edit -
	 * иначе учетки с ролью editor получают 403.
	 */
	public function testWritingActionsAllowedForGlobalEdit()
	{
		$map=$this->accessMap();
		$this->assertArrayHasKey(ArmsBaseController::PERM_EDIT, $map);
		foreach (['create','update','delete','migrate'] as $action) {
			$this->assertContains($action, $map[ArmsBaseController::PERM_EDIT], "action $action requires global edit permission");
		}
	}

	/**
	 * И по точечному праву на эту модель - для установок с гранулярными правами.
	 */
	public function testMigrateAllowedForPerModelUpdate()
	{
		$map=$this->accessMap();
		$this->assertArrayHasKey('update-users', $map);
		$this->assertContains('migrate', $map['update-users']);
	}

	/**
	 * whoami остается доступным любому авторизованному.
	 */
	public function testWhoamiStaysAuthenticatedOnly()
	{
		$map=$this->accessMap();
		$this->assertContains('whoami', $map[ArmsBaseController::PERM_AUTHENTICATED]);
	}
}
