<?php

namespace app\modules\api\controllers;

use app\models\NetIps;
use app\models\Networks;
use app\models\Schedules;
use app\models\Users;
use yii\filters\auth\HttpBasicAuth;
use yii\web\NotFoundHttpException;


class SchedulesController extends BaseRestController
{
	public $modelClass='app\models\Schedules';
	
	public $viewActions=['index','view','search','filter','status','meta-status','next-meta'];
	public $editActions=['create','update','delete','first-unused'];
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionStatus($id)
	{
		$model=$this->findModel($id);
		
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+\Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+\Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionMetaStatus($id)
	{
		$model=$this->findModel($id);
		return $model->metaAtTime(
			gmdate('Y-m-d',time()+\Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+\Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionNextMeta($id)
	{
		$model=$this->findModel($id);
		return $model->nextWorkingMeta(
			gmdate('Y-m-d',time()+\Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+\Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Finds the Schedules model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return Schedules the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = Schedules::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
}
