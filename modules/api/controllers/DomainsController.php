<?php

namespace app\modules\api\controllers;


class DomainsController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Domains';

    public function actions()
    {
        $actions = parent::actions();
		unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }
    
    public function actionView($id){
        $modelClass = $this->modelClass;
        $model = $modelClass::find()->where(['name' => strtoupper($id)])->one();
		if ($model === null) $model = $modelClass::find()->where(['LOWER(fqdn)' => strtolower($id)])->one();
		if ($model === null) $model = $modelClass::find()->where(['id' => $id])->one();
        if ($model === null)
            throw new \yii\web\NotFoundHttpException("Domain with name or id '$id' not found");
            
        return $model;
    }
    
}
