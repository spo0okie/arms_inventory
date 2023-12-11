<?php

namespace app\controllers;

use app\models\NetIps;
use app\models\Networks;
use app\models\NetworksSearch;
use Yii;
use yii\web\NotFoundHttpException;


/**
 * NetworksController implements the CRUD actions for Networks model.
 */
class NetworksController extends ArmsBaseController
{
	
	public $modelClass=Networks::class;
	
	/**
	 * @param $name
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionItemByName($name)
	{
		if (($model = Networks::findOne(['text_addr' => $name])) !== null) {
			return $this->renderPartial('item', ['model' => $model, 'static_view' => true]);
		}
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
    /**
     * Displays a single Networks model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
	{
		$model=$this->findModel($id);
		$ips= NetIps::find()
			->joinWith(['comps','techs','network.netVlan'])
			->where(['networks_id'=>$model->id])
			->orderBy(['addr'=>SORT_ASC])
			->all();
	
		return $this->render('view',
            compact('model','ips')
        );
    }
	
	/**
	 * Lists all Comps models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel = new NetworksSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount,
		]);
	}
}
