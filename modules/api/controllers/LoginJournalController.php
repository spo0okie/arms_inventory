<?php

namespace app\modules\api\controllers;



class LoginJournalController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\LoginJournal';

    public function actions()
    {
        $actions = parent::actions();
		unset($actions['index']);
        $actions[]='search';
        return $actions;
    }
	
    
    
	/**
	 * Ищет запись в бд по компу, логину и времени.
	 * Это не для пользовательских запросов, т.к. время надо передать с точностью для секунды
	 * Это для скриптов, чтобы можно было понять есть уже нужная запись в журнале или нет.
	 * @param $user_login
	 * @param $comp_name
	 * @param $time
	 * @return array|null|\yii\db\ActiveRecord
	 * @throws \yii\web\NotFoundHttpException
	 */
    public function actionSearch($user_login,$comp_name,$time,$type=0,$local_time=null){
    	//если вместе с отметкой времени входа в ПК передана текущая отметка времени
		// - корректируем ее на сдвиг текущего времени ПК относительно текущего времени сервера
		//(случай сбитых часов на ПК)
    	if ($local_time)
    		$time=$time-$local_time+time();
	    $record = \app\models\LoginJournal::find()
		    ->andFilterWhere(['comp_name' => $comp_name])
		    ->andFilterWhere(['user_login' => $user_login])
			->andFilterWhere(['time' => date('Y-m-d H:i:s',$time)])
			->andFilterWhere(['type' => $type])
		    ->one();
        if (is_null($record))
            throw new \yii\web\NotFoundHttpException("Record not found");
        return $record;
    }
}
