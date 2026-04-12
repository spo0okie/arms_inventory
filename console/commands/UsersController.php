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
 * Консольный контроллер для управления учётными записями пользователей (Users).
 *
 * Использование:
 *   yii users/passwd <login>
 */
class UsersController extends Controller
{
	/**
	 * Интерактивно меняет пароль пользователя через консольный prompt.
	 *
	 * Запрашивает новый пароль дважды (с маскировкой символов).
	 * При несовпадении паролей выводит сообщение об ошибке и завершается с UNSPECIFIED_ERROR.
	 * При успехе вызывает setPassword() и сохраняет модель.
	 *
	 * Использование: yii users/passwd <login>
	 *
	 * @param string $login Логин пользователя (Users.login)
	 * @return int ExitCode::OK при успехе, ExitCode::UNSPECIFIED_ERROR при ошибке
	 */
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
