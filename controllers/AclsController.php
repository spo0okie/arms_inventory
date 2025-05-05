<?php

namespace app\controllers;

use app\components\Forms\assets\ArmsFormAsset;
use app\models\Aces;
use app\models\Acls;
use Yii;


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
	
	
	/**
	 * По той простой причине, что создавать просто ACL без единого ACE это не интуитивно и надо сразу указывать
	 * КТО, КУДА и КАКОЙ доступ имеет, мы сделали форму сразу для ACL+ACE
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $model */
		$model = new $this->modelClass();
		$ace = new Aces();
		
		if ($model->load(Yii::$app->request->post())){
			if($model->validate()) {
				if ($ace->load(Yii::$app->request->post())){
					if($ace->validate()) {
						//успех по обеим моделям
						$model->save();
						$model->refresh();
						$ace->acls_id=$model->id;
						$ace->save();
						return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
					} else {
						//неудача по ACE
						$model->load(Yii::$app->request->get());
						$ace->load(Yii::$app->request->get());
						return $this->defaultRender('create', ['model' => $model,'ace'=>$ace]);
					}
				}
				//успех ACL, отсутствует ACE
				$model->save();
				return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
			}
		}
		
		//неудача ACL
		$model->load(Yii::$app->request->get());
		$ace->load(Yii::$app->request->get());
		return $this->defaultRender('create', ['model' => $model,'ace'=>$ace]);
	}
}
