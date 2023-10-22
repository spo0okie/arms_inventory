<?php

namespace app\controllers;

use app\models\NetworksSearch;
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
		$searchModel = new NetworksSearch();
		$searchModel->segments_id=$id;
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
	
		return $this->render('view', [
            'model' => $this->findModel($id),
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
        ]);
    }

}
