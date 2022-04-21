<?php

namespace app\modules\api\controllers;

use app\models\Domains;


class CompsController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Comps';
    
    public function actions()
    {
        $actions = parent::actions();
		unset($actions['index']);
		$actions[]='search';
        return $actions;
    }
    
    public function actionSearch($name,$domain=null,$ip=null){
    	return \app\controllers\CompsController::searchModel($name,$domain,$ip);
    }
    
}
