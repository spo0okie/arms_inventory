<?php

namespace app\controllers;

use app\models\NetworksSearch;
use app\models\ServicesSearch;
use Yii;
use app\models\Segments;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;


/**
 * SegmentsController implements the CRUD actions for Segments model.
 */
class SegmentsController extends ArmsBaseController
{
	public $modelClass=Segments::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['list'],
		]);
	}
	
	public function actionList() {
		$query=($this->modelClass)::find();
		$model= new $this->modelClass();
		if ($model->hasAttribute('archived')) {
			if (!Yii::$app->request->get('showArchived',$this->defaultShowArchived))
				$query->where(['not',['IFNULL(archived,0)'=>1]]);
		}
		
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => false,
			'sort'=>false
		]);
		
		return $this->renderAjax('table-compact', [
			'dataProvider' => $dataProvider,
		]);
	}

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
