<?php

namespace app\modules\api\controllers;

use app\models\Scans;
use yii\web\Response;
use yii\web\UploadedFile;


class ScansController extends \yii\rest\ActiveController
{
	
	public $modelClass='app\models\Scans';
	
	public function actions()
	{
		$actions = parent::actions();
		$actions[]='upload';
		return $actions;
	}
	
	/**
	 * Creates a new Scans model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionUpload()
	{
		$model = new Scans();
		$model->scanFile = UploadedFile::getInstanceByName('scanFile');
		$model->contracts_id = \Yii::$app->request->post('contracts_id');
		if (!$model->validate()) return $model->getErrors();
		if (!$model->upload()) return '{"error":"upload err"}';
		if (!$model->save(false)) return '{"error":"model saving error"}';
		return $model;
		
		
	}/**/
}
