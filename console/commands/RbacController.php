<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 28.01.2020
 * Time: 11:09
 */

namespace app\console\commands;

use app\console\ConsoleException;
use app\models\Users;
use yii\console\Controller;


/**
 * Консольный контроллер для управления RBAC (ролями и правами доступа).
 *
 * Использование:
 *   yii rbac/init
 *   yii rbac/grant <role> <login>
 *   yii rbac/revoke <role> <login>
 */
class RbacController extends Controller
{
	/**
	 * Инициализирует базовую роль RBAC — создаёт роль «admin» в authManager.
	 *
	 * Выполняется один раз при первоначальной настройке системы.
	 *
	 * Использование: yii rbac/init
	 *
	 * @return void
	 */
	public function actionInit()
	{
		$authManager = \Yii::$app->authManager;
		
		// Create roles
		$admin=$authManager->createRole('admin');
		$authManager->add($admin);
	}
	
	/**
	 * Назначает роль RBAC пользователю по логину.
	 *
	 * Бросает ConsoleException, если роль или пользователь не найдены.
	 *
	 * Использование: yii rbac/grant <role> <login>
	 *
	 * @param string $role  Имя роли RBAC (например, «admin»)
	 * @param string $login Логин пользователя (Users.login)
	 * @return void
	 */
	public function actionGrant($role,$login) {
		$authManager = \Yii::$app->authManager;
		
		$rbacRole=$authManager->getRole($role);
		if (!is_object($rbacRole))
			throw new ConsoleException("Role $role not found");
		
		$user=$this->getUser($login);
		$authManager->assign($rbacRole,$user->id);
		echo "OK\n";
	}
	
	/**
	 * Отзывает роль RBAC у пользователя по логину.
	 *
	 * Бросает ConsoleException, если роль или пользователь не найдены.
	 *
	 * Использование: yii rbac/revoke <role> <login>
	 *
	 * @param string $role  Имя роли RBAC (например, «admin»)
	 * @param string $login Логин пользователя (Users.login)
	 * @return void
	 */
	public function actionRevoke($role,$login) {
		$authManager = \Yii::$app->authManager;
		
		$rbacRole=$authManager->getRole($role);
		if (!is_object($rbacRole))
			throw new ConsoleException("Role $role not found");
		
		$user=$this->getUser($login);
		$authManager->revoke($rbacRole,$user->id);
		echo "OK\n";
	}
	
	/**
	 * Возвращает объект пользователя по логину.
	 *
	 * @param string $login Логин пользователя
	 * @return Users
	 * @throws ConsoleException Если пользователь не найден
	 */
	public function getUser($login) {
		$user = Users::findByLogin($login);
		if (!is_object($user))
			throw new ConsoleException("User $login not found");
		return $user;
	}
}