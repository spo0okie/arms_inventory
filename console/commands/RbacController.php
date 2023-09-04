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


class RbacController extends Controller
{
	public function actionInit()
	{
		$authManager = \Yii::$app->authManager;
		
		// Create roles
		$admin=$authManager->createRole('admin');
		$authManager->add($admin);
	}
	
	public function actionGrant($role,$login) {
		$authManager = \Yii::$app->authManager;
		
		$rbacRole=$authManager->getRole($role);
		if (!is_object($rbacRole))
			throw new ConsoleException("Role $role not found");
		
		$user=$this->getUser($login);
		$authManager->assign($rbacRole,$user->id);
		echo "OK\n";
	}
	
	public function actionRevoke($role,$login) {
		$authManager = \Yii::$app->authManager;
		
		$rbacRole=$authManager->getRole($role);
		if (!is_object($rbacRole))
			throw new ConsoleException("Role $role not found");
		
		$user=$this->getUser($login);
		$authManager->revoke($rbacRole,$user->id);
		echo "OK\n";
	}
	
	public function getUser($login) {
		$user = Users::findByLogin($login);
		if (!is_object($user))
			throw new ConsoleException("User $login not found");
		return $user;
	}
}