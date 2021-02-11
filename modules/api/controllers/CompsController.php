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
    
    public function actionSearch($domain=null,$name,$ip=null){
		$modelClass = $this->modelClass;
		$notFoundDescription="Comp with name '$name'";
		$query=$modelClass::find()->where(['name'=>strtoupper($name)]);

	
		//добавляем фильтрацию по IP если он есть
		if (!is_null($ip)) {
			$query->andFilterWhere(['like','ip',$ip]);
			$notFoundDescription.=" with IP $ip";
		}
	
		$notFoundDescription.=" not found";
		
		//добавляем фильтрацию по домену если он есть
    	if (!is_null($domain)) {
			if (is_null($domainObj=Domains::find()
				->where(['name'=>strtoupper($domain)])
				->one()
			))
				throw new \yii\web\NotFoundHttpException("Domain '$domain' not found");
			$query->andFilterWhere(['domain_id'=>$domainObj->id]);
			$notFoundDescription.=" in domain $domain";
		}
	
		
		
        $model = $query->one();
        
        
        if ($model === null)
            throw new \yii\web\NotFoundHttpException($notFoundDescription);
                
            return $model;
    }
    
}
