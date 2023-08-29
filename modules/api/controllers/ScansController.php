<?php

namespace app\modules\api\controllers;

use app\models\Scans;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;


class ScansController extends BaseRestController
{
	
	public $modelClass='app\models\Scans';
	
	public function actions()
	{
		return array_merge(parent::actions(),['upload','download']);
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
	}
	
	public function actionDownload($id) {
		$model = Scans::findOne($id);
		if (!is_object($model)) throw new NotFoundHttpException('Requested scan not found');
		
		if ($model->fileExists) {
			return \Yii::$app->response->sendFile($model->fsFname, $model->name);
		}
	}
}
