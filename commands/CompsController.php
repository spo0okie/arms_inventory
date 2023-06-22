<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Comps;
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
class CompsController extends Controller
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
	

	public function actionFixDupes()
	{
		if (is_array($comps=\app\models\Comps::find()->all()))
			foreach ($comps as $comp)
				if ($comp->domain_id && is_array($dupes=$comp->dupes) && count($dupes)) {
					foreach ($dupes as $dupe) if (!$dupe->domain_id) {
						echo "{$comp->domainName} absorbing {$dupe->domainName}\n";
						$comp->absorbComp($dupe);
					}
				}
			
		return ExitCode::OK;
	}
	
	public function actionResave()
	{
		foreach (\app\models\Comps::find()->all() as $comp) {
			/** @var Comps $comp */
			$comp->silentSave();
		}
		
		return ExitCode::OK;
	}
}
