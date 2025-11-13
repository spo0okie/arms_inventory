<?php

namespace app\console\commands;

use app\models\LoginJournal;
use yii\console\Controller;
use yii\console\ExitCode;


class LoginJournalController extends Controller
{

	
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
