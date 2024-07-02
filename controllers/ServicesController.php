<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\AcesSearch;
use app\models\CompsSearch;
use app\models\Places;
use app\models\ServiceConnectionsSearch;
use app\models\TechsSearch;
use Yii;
use app\models\Services;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use app\models\ServicesSearch;
use yii\web\Response;

/**
 * ServicesController implements the CRUD actions for Services model.
 */
class ServicesController extends ArmsBaseController
{
	
	public $modelClass=Services::class;
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['index-by-users','card','card-support','json-preview','os-list','aces-list'],
		]);
	}
	
	/**
	 * Displays model fields in JSON.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @noinspection PhpUnusedElementInspection
	 */
	public function actionJsonPreview(int $id)
	{
		$model=$this->findModel($id);
		$response=[];
		foreach ($model->extraFields() as $field) {
			$response[$field]=$model->$field;
		}
		Yii::$app->response->format = Response::FORMAT_JSON;
		return $response;
	}
	
	/**
	 * Displays a card for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionCard(int $id)
	{
		return $this->renderPartial('card', [
			'model' => $this->findModel($id),
		]);
	}
	
	/**
	 * Displays a tooltip for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionCardSupport(int $id)
	{
		return $this->renderPartial('card-support', [
			'model' => $this->findModel($id),
		]);
	}
	
	
	/**
	 * Lists all Services models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		
		$searchModel = new ServicesSearch();

		$searchModel->parent_id=Yii::$app->request->get('showChildren',false);
		$searchModel->archived=Yii::$app->request->get('showArchived',false);
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchParent=clone $searchModel;
		$switchParent->parent_id=!$switchParent->parent_id;
		$switchParentCount=$switchParent->search(Yii::$app->request->queryParams)->totalCount;
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$this->view->params['layout-container'] = 'container-fluid';
		
		Services::cacheAllItems();
		Places::cacheAllItems();
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchParentCount' => $switchParentCount,
			'switchArchivedCount' => $switchArchivedCount,
		]);
	}
	
	/**
	 * Lists all Services models.
	 * @param array $disabled_ids
	 * @return mixed
	 */
	public function actionIndexByUsers(array $disabled_ids=[])
	{
		Services::cacheAllItems();
		$searchModel = new ServicesSearch();
		$searchModel->directlySupported=true;
		$searchModel->parent_id=true;  //в т.ч. дочерние
		$searchModel->archived=false; //должен отсутствовать
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$this->view->params['layout-container'] = 'container-fluid';
		
		return $this->render('list-by-users', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'disabled_ids'=>$disabled_ids
		]);
	}
	
	/**
	 * Список ОС рекурсивно задействованные в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionOsList(int $id)
	{
		/** @var Services $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$compsSearch=new CompsSearch();
		$techsSearch=new TechsSearch();
		
		// через compsRecursive и techsRecursive найдем нужные объекты и через search модели подгрузим джойны
		$comps=$model->compsRecursive;
		$techs=$model->techsRecursive;
		
		//по умолчанию не можем передать списки id в search модели, т.к. пустой поиск найдет все вместо ничего
		$allModels=[];
		
		if (count($comps)) $allModels=array_merge(
			$allModels,
			$compsSearch->search(['CompsSearch'=>['ids'=>array_keys($comps)]])->models
		);
		
		if (count($techs)) $allModels=array_merge(
			$allModels,
			$techsSearch->search(['TechsSearch'=>['ids'=>array_keys($techs)]])->models
		);
		
		$dataProvider=new ArrayDataProvider([
			'allModels' => $allModels,
			'key'=>'id',
			'sort' => [
				'attributes'=> [
					'name'=>[
						'asc'=>['inServicesName'=>SORT_ASC],
						'desc'=>['inServicesName'=>SORT_DESC],
					],
					'ip',
					'mac',
					'raw_ver',
					'services_ids'=>[
						'asc'=>['servicesNames'=>SORT_ASC],
						'desc'=>['servicesNames'=>SORT_DESC],
					],
					'comment',
				],
				'defaultOrder' => [
					'name' => SORT_ASC
				]
			],
			'pagination' => false,
		]);
		return $this->renderAjax('comps-list', [
			'model'=>$model,
			'dataProvider' => $dataProvider,
		]);
	}



	/**
	 * Список связей в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionConnectionsList(int $id)
	{
		/** @var Services $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new ServiceConnectionsSearch();
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$ids=is_array($children)?ArrayHelper::getArrayField($children,'id'):[];
		$ids[]=$model->id;
		
		
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(
			Yii::$app->request->queryParams,
			['ServiceConnectionsSearch'=>['services_ids'=>$ids]]
		));
		
		return $this->renderAjax('connections-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model
		]);
	}
	
	/**
	 * Список связей в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionAcesList(int $id)
	{
		/** @var Services $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$ids=is_array($children)?ArrayHelper::getArrayField($children,'id'):[];
		$ids[]=$model->id;
		
		
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['services_subject_ids'=>$ids]]
		));
		
		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model
		]);
	}

	/**
	 * Список связей в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionAclsList(int $id)
	{
		/** @var Services $model */
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$ids=is_array($children)?ArrayHelper::getArrayField($children,'id'):[];
		$ids[]=$model->id;
		
		
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['services_resource_ids'=>$ids]]
		));
		
		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model
		]);
	}
}
