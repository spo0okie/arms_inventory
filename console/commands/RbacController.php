<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 28.01.2020
 * Time: 11:09
 */

namespace app\console\commands;

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
}