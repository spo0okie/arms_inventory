<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\Comps;
use app\models\Soft;
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
class SoftController extends Controller
{
	
	public function actionResave()
	{
		Soft::$disable_cache=true;
		Soft::$disable_rescan=true;
		foreach (\app\models\Soft::find()->all() as $soft) {
			/** @var Soft $soft */
			echo "{$soft->name}\n";
			$soft->silentSave();
		}
		
		return ExitCode::OK;
	}
}
