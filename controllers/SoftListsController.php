<?php

namespace app\controllers;

use app\models\SoftSearch;
use Yii;
use app\models\SoftLists;
use yii\web\NotFoundHttpException;


/**
 * SoftListsController implements the CRUD actions for SoftLists model.
 */
class SoftListsController extends ArmsBaseController
{
	public $modelClass=SoftLists::class;


    /**
     * Displays a single SoftLists model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
		$searchModel = new SoftSearch();
		$searchModel->soft_lists_ids=[$id];
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    	
        return $this->render('view', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
            'model' => $this->findModel($id),
        ]);
    }

}
