<?php

namespace app\modules\api\controllers;



class LoginJournalController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\LoginJournal';

    public function actions()
    {
        $actions = parent::actions();
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
    public function actionSearch($user_login,$comp_name,$time){
	    $record = \app\models\LoginJournal::find()
		    ->andFilterWhere(['comp_name' => $comp_name])
		    ->andFilterWhere(['user_login' => $user_login])
		    ->andFilterWhere(['time' => date('Y-m-d H:i:s',$time)])
		    ->one();
        if (is_null($record))
            throw new \yii\web\NotFoundHttpException("Record not found");
        return $record;
    }
}
