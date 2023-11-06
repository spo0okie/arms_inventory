<?php

namespace app\controllers;

use app\models\Places;
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
			'view'=>['index-by-users','card','card-support','json-preview','os-list','techs-list'],
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
		
		Services::cacheAllItems();
		Places::cacheAllItems();
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
		//$comps=$model->compsRecursive;
		$dataProvider=new ArrayDataProvider([
			'allModels' => array_merge($model->compsRecursive,$model->techsRecursive),
			'key'=>'id',
			'sort' => [
				'attributes'=> [
					'name',
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
}
