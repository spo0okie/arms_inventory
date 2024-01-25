<?php

namespace app\controllers;

use app\models\ui\SmsForm;
use Yii;


/**
 * UiTablesColsController implements the CRUD actions for UiTablesCols model.
 */
class SmsController extends ArmsBaseController
{

	public function accessMap()
	{
		return [ArmsBaseController::PERM_EDIT=>['send']];
	}
	
	
    public function actionSend()
	{
		$model = new SmsForm();
		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			$model->send();
			return $this->defaultRender('send-response', ['model' => $model,]);
		}
		$model->load(Yii::$app->request->get());
		return $this->defaultRender('send-form', ['model' => $model,]);
	}
}
