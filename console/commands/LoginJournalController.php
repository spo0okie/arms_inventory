<?php

namespace app\console\commands;

use app\models\LoginJournal;
use yii\console\Controller;
use yii\console\ExitCode;


/**
 * Консольный контроллер для управления журналом входов пользователей.
 *
 * Использование:
 *   yii login-journal/rescan-comp <comp_id>
 */
class LoginJournalController extends Controller
{

	/**
	 * Сбрасывает привязку записей журнала входов к указанному ПК.
	 *
	 * Для всех записей LoginJournal с comps_id = $comp_id устанавливает
	 * comps_id = NULL и вызывает silentSave(), что инициирует повторное
	 * определение ПК при следующей обработке.
	 *
	 * Использование: yii login-journal/rescan-comp <comp_id>
	 *
	 * @param int|string $comp_id Идентификатор ПК (Comps.id), чьи записи журнала нужно сбросить
	 * @return int ExitCode::OK
	 */
	public function actionRescanComp($comp_id)
	{
		if (!$comp_id) $comp_id=null;
		foreach (LoginJournal::find()->where(['comps_id'=>$comp_id])->all() as $rec) {
			echo "{$rec->id}:{$rec->comp_name}\n";
			$rec->comps_id=null;
			$rec->silentSave();
		}
		
		return ExitCode::OK;
	}

}
