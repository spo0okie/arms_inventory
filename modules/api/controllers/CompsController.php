<?php

namespace app\modules\api\controllers;

use app\models\Domains;


class CompsController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Comps';
    
    public function actions()
    {
        $actions = parent::actions();
        $actions[]='search';
        return $actions;
    }
    
    public function actionSearch($domain,$name){
        if (is_null($domainObj=Domains::find()->where(['name'=>strtoupper($domain)])->one()))
            throw new \yii\web\NotFoundHttpException("Domain '$domain' not found");
        $modelClass = $this->modelClass;
        $model = $modelClass::find()->where([
            'name' => strtoupper($name),
            'domain_id' => $domainObj->id, 
        ])->one();
        if ($model === null)
            throw new \yii\web\NotFoundHttpException("Comp with name '$name' not found in domain $domain (".$domainObj->id.")");
                
            return $model;
    }
    
}
