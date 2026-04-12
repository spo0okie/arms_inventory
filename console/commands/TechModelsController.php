<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\TechModels;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольный контроллер для управления записями моделей оборудования (TechModels).
 *
 * Использование:
 *   yii tech-models/resave
 */
class TechModelsController extends Controller
{
	/**
	 * Пересохраняет все записи моделей оборудования через silentSave().
	 *
	 * Применяется для принудительного пересчёта вычисляемых полей и
	 * обновления зависимостей без изменения данных.
	 * Выводит имя каждой записи в stdout.
	 *
	 * Использование: yii tech-models/resave
	 *
	 * @return int ExitCode::OK
	 */
	public function actionResave()
	{
		foreach (\app\models\TechModels::find()->all() as $model) {
			/** @var TechModels $model */
			echo "{$model->name}\n";
			$model->silentSave();
		}
		
		return ExitCode::OK;
	}
}
