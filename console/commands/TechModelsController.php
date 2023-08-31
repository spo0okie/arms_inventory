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
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class TechModelsController extends Controller
{
	
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
