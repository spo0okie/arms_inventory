<?php

namespace app\controllers;

use app\models\NetworksSearch;
use app\models\ServicesSearch;
use Yii;
use app\models\Segments;
use yii\web\NotFoundHttpException;


/**
 * SegmentsController implements the CRUD actions for Segments model.
 */
class SegmentsController extends ArmsBaseController
{

	public $modelClass=Segments::class;

    /**
     * Displays a single Segments model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
		$networksSearch = new NetworksSearch();
		$networksSearch->segments_id=$id;
		$networksSearch->archived= Yii::$app->request->get('showArchived',false);
		$networksProvider = $networksSearch->search(Yii::$app->request->queryParams);
	
		$servicesSearch = new ServicesSearch();
		$servicesSearch->segment_id=$id;
		$servicesSearch->archived=Yii::$app->request->get('showArchived',false);
	
		$servicesProvider = $servicesSearch->search(Yii::$app->request->queryParams);
	
	
		return $this->render('view', [
            'model' => $this->findModel($id),
			'networksSearch' => $networksSearch,
			'networksProvider' => $networksProvider,
			'servicesSearch' => $servicesSearch,
			'servicesProvider' => $servicesProvider,
        ]);
    }

}
