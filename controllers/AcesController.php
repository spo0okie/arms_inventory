<?php

namespace app\controllers;

use app\components\Forms\assets\ArmsFormAsset;
use app\models\AccessTypes;
use app\models\Aces;
use app\models\Acls;
use Yii;
use yii\web\Response;


/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends ArmsBaseController
{
	public function testCreate(): array
	{
		return self::skipScenario('default', 'ACE create requires coupled ACL+ACE fixture set');
	}

	public function testItem(): array
	{
		return self::skipScenario('default', 'ACE item rendering requires coupled ACL+ACE fixture set');
	}

	public function testTtip(): array
	{
		return self::skipScenario('default', 'ACE tooltip requires coupled ACL+ACE fixture set');
	}

	public function testView(): array
	{
		return self::skipScenario('default', 'ACE view requires coupled ACL+ACE fixture set');
	}

	public function testUpdate(): array
	{
		return self::skipScenario('default', 'ACE update requires coupled ACL+ACE fixture set');
	}

	public function testDelete(): array
	{
		return self::skipScenario('default', 'ACE delete requires coupled ACL+ACE fixture set');
	}

	public function testAsyncGrid(): array
	{
		return self::skipScenario('default', 'ACE async-grid requires coupled ACL+ACE fixture set');
	}

	public function testValidate(): array
	{
		return self::skipScenario('default', 'ACE validate requires coupled ACL+ACE fixture set');
	}

	public function testAccessTypesForm(): array
	{
		return self::skipScenario('default', 'requires complex data preparation');
	}
	public $modelClass=Aces::class;
	
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['access-types-form']
		]);
	}
	
	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Aces $model */
		$acl=$model->acl;
		$schedules_id=is_object($acl)?$acl->schedules_id:0;
		return ($schedules_id)?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index'];
    }
	
	/**
	 * Возвращает параметры типов доступа с простановкой дополнительных(дочерних), блокировкой отключения дочерних
	 * и сетевыми параметрами по умолчанию
	 * @param $access_types_ids
	 * @return array
	 */
    public function actionAccessTypesForm(array $access_types_ids) {
		Yii::$app->response->format=Response::FORMAT_JSON;
		return AccessTypes::bundleAccessTypes($access_types_ids);
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
		/** @var Aces $model */
		$model = new $this->modelClass();
		$acl = new Acls();
		$model->load(Yii::$app->request->get());
		$acl->load(Yii::$app->request->get());
		if ($model->load(Yii::$app->request->post())){
			if($model->validate()) {
				if ($acl->load(Yii::$app->request->post())){
					if($acl->validate()) {
						//успех по обеим моделям
						$acl->save();
						$acl->refresh();
						$model->acls_id=$model->id;
						$model->save();
						return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
					} else {
						//неудача по ACL
						return $this->defaultRender('create', ['model' => $model,'acl'=>$acl]);
					}
				}
				//успех ACE, отсутствует ACL
				$model->save();
				return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
			}
		}
		
		//неудача ACE
		return $this->defaultRender('create', ['model' => $model,'acl'=>$acl]);
	}
}
