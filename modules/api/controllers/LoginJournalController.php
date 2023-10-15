<?php

namespace app\modules\api\controllers;



use app\models\LoginJournal;
use yii\db\ActiveRecord;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;

class LoginJournalController extends ActiveController
{
    
    public $modelClass='app\models\LoginJournal';

	/**
	 * Ищет запись в бд по компу, логину и времени.
	 * Это не для пользовательских запросов, т.к. время надо передать с точностью для секунды
	 * Это для скриптов, чтобы можно было понять есть уже нужная запись в журнале или нет.
	 * @param string $user_login
	 * @param string $comp_name
	 * @param string $time
	 * @param int    $type
	 * @param int|null   $local_time
	 * @return array|null|ActiveRecord
	 * @throws NotFoundHttpException
	 */
    public function actionSearch(string $user_login, string $comp_name, string $time, int $type=0, $local_time=null){
    	//если вместе с отметкой времени входа в ПК передана текущая отметка времени
		// - корректируем ее на сдвиг текущего времени ПК относительно текущего времени сервера
		//(случай сбитых часов на ПК)
    	if ($local_time)
    		$time=$time-$local_time+time();
	    $record = \app\models\LoginJournal::find()
		    ->andFilterWhere(['comp_name' => $comp_name])
		    ->andFilterWhere(['user_login' => $user_login])
			->andFilterWhere(['>','time',date('Y-m-d H:i:s',$time-LoginJournal::$maxTimeShift)])
			->andFilterWhere(['<','time',date('Y-m-d H:i:s',$time+LoginJournal::$maxTimeShift)])
			->andFilterWhere(['type' => $type])
		    ->one();
        if (is_null($record))
            throw new NotFoundHttpException("Record not found");
        return $record;
    }
}
