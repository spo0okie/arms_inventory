<?php

namespace app\controllers;

use Yii;
use app\models\OrgPhones;
use yii\data\ActiveDataProvider;

/**
 * OrgPhonesController implements the CRUD actions for OrgPhones model.
 */
class OrgPhonesController extends ArmsBaseController
{
	
	public $modelClass=OrgPhones::class;
	
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	public function routeOnUpdate($model)
	{
		/** @var OrgPhones $model */
		return $model->services_id?
			['services/view','id'=>$model->services_id]:
			['org-phones/view','id'=>$model->id];
	}
	
	public function routeOnDelete($model)
	{
		/** @var OrgPhones $model */
		return $model->services_id?
			['services/view','id'=>$model->services_id]:
			['org-phones/index'];
	}
	
	/**
     * Lists all OrgPhones models.
     * @return mixed
     */
    public function actionIndex()
    {
    	$query=OrgPhones::find();
    	if (!Yii::$app->request->get('showArchived',false))
    		$query->where(['not',['archived'=>1]]);

    	
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
}
