<?php

namespace app\controllers;


use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use kartik\markdown\Markdown;
use Yii;
use yii\web\NotFoundHttpException;


/**
 * MaintenanceReqsController implements the CRUD actions for MaintenanceReqs model.
 */
class MaintenanceReqsController extends ArmsBaseController
{
	public $modelClass=MaintenanceReqs::class;
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['list'],
		]);
	}
	
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
		if ($t=Yii::$app->request->get('timestamp')) {
			return $this->renderPartial('ttip', [
				'model' => $this->findJournalRecord($id,$t),
				'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
				'absorbed' => $absorbedBy?MaintenanceReqs::findOne($absorbedBy):null,
			]);
		}
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'job' => $satisfiedBy?MaintenanceJobs::findOne($satisfiedBy):null,
			'absorbed' => $absorbedBy?MaintenanceReqs::findOne($absorbedBy):null,
		]);
	}
	
	/**
	 * Displays a tooltip for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionList()
	{
		$ouput=[];
		$ouput[]='<table>';
		foreach (($this->modelClass)::find()->All() as $item) {
			$ouput[]='<tr>';
				$ouput[]='<td>';
					$ouput[]=$item->renderItem($this->view);
				$ouput[]='</td>';
				$ouput[]='<td>';
					$ouput[]=Markdown::convert($item->description);
				$ouput[]='</td>';
			$ouput[]='</tr>';
		}
		$ouput[]='</table>';
		return implode("\n",$ouput);
	}
}
