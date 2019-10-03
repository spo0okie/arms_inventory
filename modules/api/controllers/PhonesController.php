<?php

namespace app\modules\api\controllers;

use app\models\Domains;


class PhonesController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Techs';
    
    public function actions()
    {
        //$actions = parent::actions();
        //$actions['search'];
        return ['search'];
    }
    
    public function actionSearch($num){
    	//ищем телефонный аппарат по номеру
        $tech = \app\models\Techs::find()
	        ->where(['comment' => $num ])
			->one();
        //если нашли
        if (is_object($tech)){
        	//он прикреплен к АРМ?
        	if (is_object($arm=$tech->arm)) {
        		//пользователь у АРМа есть?
        		if (is_object($user=$arm->user)) {
        			return $user->Ename;
		        }
	        }
	        if (is_object($user=$tech->user)) {
		        return $user->Ename;
	        }
        }
        throw new \yii\web\NotFoundHttpException("not found");
    }
    
}
