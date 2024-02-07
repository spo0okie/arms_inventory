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
	 * @param null $satisfiedBy Если удовлетворяется какой-то операцией (это при выводе требований в форме где также выводятся
	 *                            регламентные операции), то отобразим что требование удовлетворено
	 * @param null $absorbedBy Если в куче требований оказалось избыточным
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip(int $id, $satisfiedBy=null, $absorbedBy=null)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
			'absorbed' => $absorbedBy?MaintenanceReqs::findOne($absorbedBy):null,
		]);
	}
}
