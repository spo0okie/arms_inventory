<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\MaintenanceJobs;
use app\models\MaintenanceJobsSearch;
use Yii;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;


/**
 * MaintenanceJobsController implements the CRUD actions for MaintenanceJobs model.
 */
class MaintenanceJobsController extends ArmsBaseController
{
	public $modelClass=MaintenanceJobs::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['children-tree','index-tree'],
		]);
	}
	
	/**
	 * Lists all Services models.
	 * @return mixed
	 */
	public function actionIndexTree()
	{
		MaintenanceJobs::cacheAllItems();
		$searchModel = new MaintenanceJobsSearch();
		
		//ищем то же самое, но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
		
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		//$this->view->params['layout-container'] = 'container-fluid';
		
		$models=[];
		ArrayHelper::sortFlatTree(ArrayHelper::buildSortedTree($dataProvider->models),$models);
		
		$arrDataProvider=new ArrayDataProvider([
			'allModels'=>$models,
		]);
		
		
		return $this->render('/layouts/index', [
			'searchModel' => $searchModel,
			'dataProvider' => $arrDataProvider,
			'switchArchivedCount' => $switchArchivedCount,
			'additionalCreateButton' => ' // '.Html::a('Список','index'),
			'model' => new MaintenanceJobs(),
		]);
	}
	
	public function actionIndex ()
	{
		$this->additionalCreateButton=' // '.Html::a('Дерево','index-tree');
		return parent::actionIndex();
	}
	
	/**
	 * Список связей в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionChildrenTree(int $id)
	{
		/** @var MaintenanceJobs $model */
		$model=$this->findModel($id);
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$searchModel = new MaintenanceJobsSearch();
		$searchModel->ids=array_merge(ArrayHelper::getArrayField($children,'id'),[$id]);  //только эти
		
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		$models=[];
		ArrayHelper::sortFlatTree(ArrayHelper::buildSortedTree(
			$dataProvider->models,
			'parent_id',
			'treeChildren',
			'treeDepth',
			$model->parent_id
		),$models);
		
		$arrDataProvider=new ArrayDataProvider([
			'allModels'=>$models,
			'pagination'=>false
		]);
		
		
		return $this->renderAjax('children-tree', [
			'model' => $model,
			'dataProvider' => $arrDataProvider,
			//'switchArchivedCount' => $switchArchivedCount,
		]);
	}
}
