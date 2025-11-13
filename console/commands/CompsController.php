<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\models\Comps;
use app\models\CompsRescanQueue;
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
		if (is_array($comps= Comps::find()->all()))
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
		foreach (Comps::find()->all() as $comp) {
			/** @var Comps $comp */
			$comp->silentSave();
		}
		
		return ExitCode::OK;
	}

	public function actionRescan($count=100)
	{
		$queue=CompsRescanQueue::find()
			->groupBy(['comps_id'])
			->limit($count)
			->all();
		
		foreach ($queue as $item) {
			/** @var Comps $comp */
			$comp=Comps::findOne($item->comps_id);
			if (is_object($comp)) {
				echo $comp->fqdn."\n";
				$comp->silentSave();
			} else {
				echo $item->comp_id." missing \n";
			}
		}
		
		return ExitCode::OK;
	}
	
	public function actionFind($name)
	{
		if (is_object($comp= Comps::findByAnyName($name))) {
			/** @var Comps $comp */
			echo "{$comp->id}\n";
			return ExitCode::OK;
		}
		return ExitCode::UNAVAILABLE;
	}
}
