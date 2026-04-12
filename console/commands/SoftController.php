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
 * Консольный контроллер для управления записями программного обеспечения (Soft).
 *
 * Использование:
 *   yii soft/resave
 */
class SoftController extends Controller
{
	/**
	 * Пересохраняет все записи ПО через silentSave() с отключённым кешем и пересканированием.
	 *
	 * Перед запуском устанавливает Soft::$disable_cache = true и
	 * Soft::$disable_rescan = true для предотвращения побочных эффектов.
	 * Выводит имя каждой записи в stdout.
	 *
	 * Использование: yii soft/resave
	 *
	 * @return int ExitCode::OK
	 */
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
