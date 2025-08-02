<?php

namespace app\controllers;

use app\models\AcesSearch;
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
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['incoming-connections-list','ipam'],
		]);
	}
	
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
	
	/**
	 * Список связей в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionIncomingConnectionsList(int $id)
	{
		/** @var Networks $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		
		// получаем всех детей
		$aces=$model->getIncomingAcesEffective();
		
		foreach ($aces as $id=>$ace) {
			if (!$ace->hasIpAccess()) unset($aces[$id]);
		}
		
		$ids=array_keys($aces);

		$dataProvider = $searchModel->search(array_merge(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['ids'=>$ids]]
		));
		
		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model
		]);
	}
	
	public function actionIpam()
	{
		$baseIp = Yii::$app->request->get('baseIp', '192.168.0.0');
		$minPrefix = (int) Yii::$app->request->get('minPrefix', 29);
		$maxPrefix = (int) Yii::$app->request->get('maxPrefix', 24);
		
		$models = Networks::find()->all();
		return $this->render('ipam', compact('models','baseIp','minPrefix','maxPrefix'));
	}
}
