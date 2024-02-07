<?php

namespace app\controllers;


use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use yii\web\NotFoundHttpException;


/**
 * MaintenanceReqsController implements the CRUD actions for MaintenanceReqs model.
 */
class MaintenanceReqsController extends ArmsBaseController
{
	public $modelClass=MaintenanceReqs::class;
	
	/**
	 * Displays a tooltip for single model.
	 * @param int  $id
	 * @param null $satisfiedBy	Если удовлетворяется какой-то операцией (это при выводе требований в форме где также выводятся
	 * 							регламентные операции), то отобразим что требование удовлетворено
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip(int $id, $satisfiedBy=null)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
		]);
	}
}
