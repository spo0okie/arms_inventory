<?php

namespace app\controllers;

use Yii;
use app\models\Acls;


/**
 * AclsController implements the CRUD actions for Acls model.
 */
class AclsController extends ArmsBaseController
{
	public $modelClass=Acls::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['ace-cards']
		]);
	}
	
	public function actionAceCards(int $id) {
		return $this->defaultRender('ace-cards',['model'=>$this->findModel($id)]);
	}
	
	public function routeOnUpdate($model)
	{
		if (Yii::$app->request->get('accept')) return ['update','id'=>$model->id];
		return $model->schedules_id?
			['/scheduled-access/view','id'=>$model->schedules_id]:
			['view','id'=>$model->id];
	}
	
	
	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Acls $model */
    	$schedules_id=$model->schedules_id;
        return $schedules_id?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index-acl'];
    }
}
