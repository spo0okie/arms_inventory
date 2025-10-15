<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\Comps;
use app\models\Soft;
use app\models\Users;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UsersController extends Controller
{
	
	public function actionPasswd($login)
	{
		$user=Users::findByLogin($login);
		if (!is_object($user)) {
			echo "User $login not found\n";
			return ExitCode::UNSPECIFIED_ERROR;
		}
		//запрашиваем пароль через консоль:
		$passwd = $this->prompt('Enter new password: ', [
			'required' => true,
			'mask' => '*',
		]);
		$passwd2 = $this->prompt('Repeat new password: ', [
			'required' => true,
			'mask' => '*',
		]);
		if ($passwd!=$passwd2) {
			echo "Password mismatch\n";
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$user->setPassword($passwd);
		$user->save();
		return ExitCode::OK;
	}
}
