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
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['children-tree','index-tree'],
		]);
	}
	
	/**
	 * Список заданий технического обслуживания в виде иерархического дерева.
	 *
	 * Строит плоское дерево из всех MaintenanceJobs с учётом вложенности (parent_id).
	 * Пагинация отключена — дерево отображается целиком.
	 * Также вычисляет количество записей при переключении фильтра архивных заданий
	 * (для кнопки «показать/скрыть архивные»).
	 *
	 * GET-параметры:
	 *  - showArchived (bool, опционально) — включить архивные задания.
	 *  - Любые фильтры из MaintenanceJobsSearch (через queryParams).
	 *
	 * @return mixed
	 */
	public function actionIndexTree()
	{
		MaintenanceJobs::cacheAllItems();
		//Отключаем пагинацию, так как дерево можно построить только целиком
		$searchModel = new MaintenanceJobsSearch(['disablePagination'=>true]);
		
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
			'pagination'=>false
		]);
		
		
		return $this->render('/layouts/index', [
			'searchModel' => $searchModel,
			'dataProvider' => $arrDataProvider,
			'switchArchivedCount' => $switchArchivedCount,
			'additionalCreateButton' => ' // '.Html::a('Список','index'),
			'model' => new MaintenanceJobs(),
		]);
	}
	
	/**
	 * Acceptance test data for IndexTree.
	 *
	 * Вызывает action без GET-параметров и без предварительных данных.
	 * Это минимальный smoke-тест: проверяет, что страница дерева отрисовывается
	 * с кодом 200 даже при пустой таблице.
	 *
	 * Для полноценного теста необходимо создать несколько MaintenanceJobs
	 * с parent_id через getTestData()['full'] и убедиться, что дерево
	 * корректно строится и сортируется.
	 */
	public function testIndexTree(): array
	{
		$this->getTestData();
		return [[]];//default smoke-test: no GET params, checks 200 with existing data
	}
	
	public $modelClass=MaintenanceJobs::class;
	
	/**
	 * Список заданий технического обслуживания в табличном виде.
	 *
	 * Переопределяет базовый actionIndex: добавляет кнопку переключения
	 * на представление в виде дерева («Дерево» → /maintenance-jobs/index-tree).
	 *
	 * GET-параметры: любые фильтры MaintenanceJobsSearch (через queryParams).
	 *
	 * @return mixed
	 */
	public function actionIndex ()
	{
		$this->additionalCreateButton=' // '.Html::a('Дерево','index-tree');
		return parent::actionIndex();
	}
	
	/**
	 * Дерево дочерних заданий технического обслуживания.
	 *
	 * Рекурсивно собирает все дочерние задания (по parent_id) для указанного
	 * задания и рендерит их в виде дерева через partial-шаблон 'children-tree'.
	 * Используется для AJAX-вставки поддерева в родительский список.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор корневого MaintenanceJobs.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если задание с данным id не найдено
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

	/**
	 * Acceptance test data for ChildrenTree.
	 *
	 * Пропускается, так как для теста необходимо:
	 *  - создать родительское задание через getTestData()['full'];
	 *  - создать хотя бы одно дочернее задание с parent_id = родительское.
	 * Если ModelFactory поддерживает атрибут parent_id (можно проверить
	 * через getTestData()), создание дочернего задания можно реализовать
	 * без skip. Пока данные для дерева не гарантируются — тест пропускается.
	 */
	public function testChildrenTree(): array
	{
		$parent = \app\generation\ModelFactory::create(\app\models\MaintenanceJobs::class, ['empty' => false]);
		\app\generation\ModelFactory::create(\app\models\MaintenanceJobs::class, ['overrides' => ['parent_id' => $parent->id]]);
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $parent->id],
			'response' => 200,
		]];
	}

}
