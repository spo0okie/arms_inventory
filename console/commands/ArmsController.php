<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольный контроллер для управления записями АРМ (рабочих мест).
 *
 * Использование:
 *   yii arms/index
 *   yii arms/re-save
 */
class ArmsController extends Controller
{
	/**
	 * Заглушка — точка входа контроллера.
	 *
	 * Использование: yii arms/index
	 *
	 * @return int ExitCode::OK
	 */
	public function actionIndex()
	{
		return ExitCode::OK;
	}
	

	/**
	 * Пересохраняет все записи АРМ (Techs) через штатный save().
	 *
	 * Применяется для принудительного запуска afterSave-обработчиков
	 * и пересчёта вычисляемых полей без изменения данных.
	 *
	 * Использование: yii arms/re-save
	 *
	 * @return int ExitCode::OK
	 */
	public function actionReSave()
	{
		if (is_array($arms=\app\models\Techs::find()->all()))
			foreach ($arms as $arm) {
				echo "{$arm->num}\n";
				$arm->save(false);
			}
		return ExitCode::OK;
	}
}
