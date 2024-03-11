<?php

namespace app\modules\api\controllers;

use app\models\Schedules;
use Yii;
use yii\web\NotFoundHttpException;


class SchedulesController extends BaseRestController
{
	public $modelClass=Schedules::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['status','meta-status','next-meta'],
			'view-schedules'=>['status','meta-status','next-meta'],
		]);
	}
	
	public static $searchJoin=['acls.aces.accessTypes'];
	public static $searchFields=['accessTypeName'=>'access_types.name'];
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionStatus(int $id)
	{
		$model=$this->findModel($id);
		
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+ Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+ Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionMetaStatus(int $id)
	{
		$model=$this->findModel($id);
		return $model->metaAtTime(
			gmdate('Y-m-d',time()+ Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+ Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionNextMeta(int $id)
	{
		$model=$this->findModel($id);
		return $model->nextWorkingMeta(
			gmdate('Y-m-d',time()+ Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+ Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Finds the Schedules model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return Schedules the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel(int $id)
	{
		if (($model = Schedules::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
}
