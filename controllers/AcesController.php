<?php

namespace app\controllers;

use app\components\Forms\assets\ArmsFormAsset;
use app\models\Aces;
use app\models\Acls;
use Yii;

/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends ArmsBaseController
{
	public $modelClass=Aces::class;

	public function disabledActions()
	{
		return ['item-by-name',];
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
	 * Создаёт ACE (и при необходимости ACL) из общей формы ACL+ACE.
	 *
	 * Для POST-вызова ожидаются валидные данные для моделей Aces и Acls.
	 * При успешной валидации обеих моделей создаются записи и выполняется redirect.
	 *
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
						$acl->save();
						$acl->refresh();
						$model->acls_id=$model->id;
						$model->save();
						return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
					} else {
						return $this->defaultRender('create', ['model' => $model,'acl'=>$acl]);
					}
				}
				$model->save();
				return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
			}
		}

		return $this->defaultRender('create', ['model' => $model,'acl'=>$acl]);
	}
}
