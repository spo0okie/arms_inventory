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
		$name=strtoupper($name);
		$domainObj=null;
		if (strpos($name,'.')!==false) {
			//fqdn passed
			$tokens=explode('.',$name);
			$name=$tokens[0];
			unset($tokens[0]);
			$domain=implode('.',$tokens);
			
			//ищем домен
			if (is_null($domainObj=Domains::find()
				->where(['fqdn'=>$domain])
				->one()
			)) throw new \yii\web\NotFoundHttpException("Domain '$domain' not found");
			
		} elseif (!is_null($domain)) {
			if (is_null($domainObj=Domains::find()
				->where(['name'=>strtoupper($domain)])
				->one()
			)) throw new \yii\web\NotFoundHttpException("Domain '$domain' not found");
		}

		
		$query=$modelClass::find()->where(['name'=>strtoupper($name)]);
	
		//добавляем фильтрацию по IP если он есть
		if (!is_null($ip)) {
			//если передано несколько адресов (через пробел)
			$query->andFilterWhere(['or like','ip',explode(' ',trim($ip))]);
			$notFoundDescription.=" with IP $ip";
		}
	
		$notFoundDescription.=" not found";
		
		//добавляем фильтрацию по домену если он есть
    	if (is_object($domainObj)) {
			//добавляем домен к условию поиска
			$query->andFilterWhere(['domain_id'=>$domainObj->id]);
			$notFoundDescription.=" in domain $domain";
		}
	
        $model = $query->one();
        
        if ($model === null)
            throw new \yii\web\NotFoundHttpException($notFoundDescription);
                
            return $model;
    }
    
}
