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
 * Консольный контроллер для управления записями организационной структуры (OrgStruct).
 *
 * Использование:
 *   yii org-struct/resave
 */
class OrgStructController extends Controller
{
	/**
	 * Пересохраняет все записи организационной структуры через silentSave().
	 *
	 * Применяется для принудительного пересчёта вычисляемых полей и
	 * обновления зависимых данных без изменения бизнес-логики.
	 * Выводит имя каждой записи и результат сохранения (OK / ERR).
	 *
	 * Использование: yii org-struct/resave
	 *
	 * @return int ExitCode::OK
	 */
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
