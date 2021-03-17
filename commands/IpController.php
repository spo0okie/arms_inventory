<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

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
class IpController extends Controller
{
	/**
	 * This command echoes what you have entered as the message.
	 * @param string $message the message to be echoed.
	 * @return int Exit code
	 */
	public function actionIndex()
	{
		
		
		return ExitCode::OK;
	}
	
	public function actionRecalc()
	{
		if (is_array($comps=\app\models\Comps::find()->all())) {
			foreach ($comps as $comp) $comp->save();
		};
		if (is_array($techs=\app\models\Techs::find()->all())) {
			foreach ($techs as $tech) $tech->save();
		};
		
		return ExitCode::OK;
	}
}
