<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\OrgStruct;
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
class OrgStructController extends Controller
{
	
	public function actionResave()
	{
		foreach (\app\models\OrgStruct::find()->all() as $unit) {
			/** @var OrgStruct $unit */
			echo "{$unit->name}";
			if ($unit->silentSave()) {
				echo " - OK\n";
			} else {
				echo " - ERR:" . print_r($unit->errors,true)."\n";
			}
		}
		
		return ExitCode::OK;
	}
}
