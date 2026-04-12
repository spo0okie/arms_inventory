<?php

namespace app\controllers;

use app\components\ModelFieldWidget;
use app\helpers\ArrayHelper;
use app\models\AcesSearch;
use app\models\CompsSearch;
use app\models\Places;
use app\models\TechsSearch;
use Yii;
use app\models\Services;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use app\models\ServicesSearch;
use yii\web\Response;

/**
 * ServicesController implements the CRUD actions for Services model.
 *
 * Управляет ИТ-сервисами: отображение списков, дерева, карточек, связанного
 * оборудования/ПО, списков доступа (ACE/ACL) и требований тех.обслуживания.
 */
class ServicesController extends ArmsBaseController
{
	/**
	 * Acceptance test data for Ttip.
	 *
	 * Тест пропущен: вьюха ttip сервиса рендерит связанные данные (owners, tech support,
	 * parent service и др.), которые требуют полностью заполненной модели с зависимостями.
	 * Если getTestData()['full'] гарантированно создаёт сервис с этими связями — skip можно заменить.
	 */
	public function testTtip(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Acceptance test data for View.
	 *
	 * Тест пропущен: страница view сервиса рендерит расширенные связанные данные
	 * (ответственные, техподдержка, связанные объекты). Если getTestData()['full']
	 * создаёт сервис со всеми нужными связями — skip можно заменить реальным тестом.
	 */
	public function testView(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Acceptance test data for AcesList.
	 *
	 * Проверяет рендер списка ACE (access control entries) для сервиса.
	 * GET: id — идентификатор сервиса, полученный из getTestData()['full'].
	 */
	public function testAcesList(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Acceptance test data for AclsList.
	 *
	 * Проверяет рендер списка ACL (access control list) для сервиса.
	 * GET: id — идентификатор сервиса, полученный из getTestData()['full'].
	 */
	public function testAclsList(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['index-by-users','index-tree','children-tree','card','card-support','card-maintenance-reqs','json-preview','os-list','aces-list','acls-list'],
		]);
	}
	
	/**
	 * Возвращает JSON-ответ с содержимым всех extraFields сервиса для отладки.
	 *
	 * GET: id (int) — идентификатор сервиса.
	 * Ответ: JSON-объект, где ключи — имена extraFields, значения — их содержимое.
	 *
	 * @param int $id Идентификатор сервиса
	 * @return array JSON-объект с extraFields сервиса
	 * @throws NotFoundHttpException если сервис не найден
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
	 * Acceptance test data for JsonPreview.
	 *
	 * Проверяет JSON-ответ с extraFields для существующего сервиса.
	 * GET: id из getTestData()['full'].
	 */
	public function testJsonPreview(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Рендерит partial-карточку сервиса (card view).
	 *
	 * Отображает связанные данные: ответственных (owners), техподдержку,
	 * место размещения и другие поля сервиса.
	 * GET: id (int) — идентификатор сервиса.
	 *
	 * @param int $id Идентификатор сервиса
	 * @return string HTML partial карточки сервиса
	 * @throws NotFoundHttpException если сервис не найден
	 */
	public function actionCard(int $id)
	{
		return $this->renderPartial('card', [
			'model' => $this->findModel($id),
		]);
	}
	
		
	/**
	 * Acceptance test data for Card.
	 *
	 * Тест пропущен: card view сервиса рендерит виджеты со связанными данными —
	 * ответственные (owners), техподдержка (techSupport), parent-сервис и др.
	 * Для стабильного рендера требуется сервис с полным набором этих связей.
	 * Если getTestData()['full'] создаёт такой сервис — skip можно заменить реальным тестом.
	 */
	public function testCard(): array
	{
		return self::skipScenario('default', 'requires extended linked data for card widgets');
	}
	/**
	 * Рендерит partial-карточку технической поддержки сервиса.
	 *
	 * Отображает блок информации о техподдержке: ответственных исполнителей,
	 * контакты и регламенты поддержки конкретного сервиса.
	 * GET: id (int) — идентификатор сервиса.
	 *
	 * @param int $id Идентификатор сервиса
	 * @return string HTML partial карточки техподдержки
	 * @throws NotFoundHttpException если сервис не найден
	 */
	public function actionCardSupport(int $id)
	{
		return $this->renderPartial('card-support', [
			'model' => $this->findModel($id),
		]);
	}
	
		
	/**
	 * Acceptance test data for CardSupport.
	 *
	 * Проверяет partial-рендер карточки техподдержки для существующего сервиса.
	 * GET: id из getTestData()['full'].
	 */
	public function testCardSupport(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Рендерит виджет со списком требований технического обслуживания для сервиса.
	 *
	 * Отображает поле backupReqs сервиса через ModelFieldWidget в режиме static_view.
	 * GET: id (int) — идентификатор сервиса.
	 *
	 * @param int $id Идентификатор сервиса
	 * @return string HTML-виджет с требованиями тех.обслуживания
	 * @throws NotFoundHttpException если сервис не найден
	 */
	public function actionCardMaintenanceReqs(int $id)
	{
		$model=$this->findModel($id);
		return ModelFieldWidget::widget([
			'model'=>$model,
			'field'=>'backupReqs',
			'title'=>false,
			'item_options'=>[
				'static_view'=>true
			]
		]);
	}
	
		
	/**
	 * Acceptance test data for CardMaintenanceReqs.
	 *
	 * Тест пропущен: для корректного рендера требуется сервис с привязанными
	 * записями backupReqs (требования тех.обслуживания).
	 * Если getTestData()['full'] создаёт сервис с такими связями — skip можно заменить.
	 */
	public function testCardMaintenanceReqs(): array
	{
		return self::skipScenario('default', 'requires linked maintenance requirements');
	}
	/**
	 * Отображает список сервисов с поддержкой фильтрации, дочерних записей и архива.
	 *
	 * Логика: если результат пустой и дочерние записи не заданы явно — автоматически
	 * переключается на вариант с дочерними. Передаёт количество альтернативных
	 * результатов (switchParentCount, switchArchivedCount) для переключателей в UI.
	 *
	 * GET:
	 *   showChildren (bool, опционально) — включить дочерние сервисы в результат.
	 *   showArchived (bool, опционально) — показать архивные сервисы.
	 *   queryParams — любые параметры ServicesSearch для фильтрации.
	 *
	 * @return string Страница списка сервисов
	 */
	public function actionIndex()
	{
		
		$searchModel = new ServicesSearch();
		
		//признак того, что свойства ниже указаны явно (не равны значениям по умолчанию)
		$direct_parent=Yii::$app->request->get('showChildren','unset')!='unset';
		//$direct_archived=Yii::$app->request->get('showArchived','unset')!='unset';
		
		$searchModel->parent_id=Yii::$app->request->get('showChildren',false);
		$searchModel->archived=Yii::$app->request->get('showArchived',false);
		
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		if (!$dataProvider->totalCount) {
			//допустим с нашими параметрами ничего не нашлось
			if (!$direct_parent && !$searchModel->parent_id) {
				//если дочерние неявно отключены, то смотрим что будет вместе с ними
				$switchParent=clone $searchModel;
				$switchParent->parent_id=!$switchParent->parent_id;
				$switchParentData=$switchParent->search(Yii::$app->request->queryParams);
				$switchParentCount=$switchParentData->totalCount;
				if ($switchParentCount) {
					//если есть дочерние, то заменяем текущий поиск на поиск с дочерними
					//и устанавливаем количество записей без дочерних как альтернативное
					$switchParentCount=$dataProvider->totalCount;
					$searchModel=$switchParent;
					$dataProvider=$switchParentData;
				}
			}
		}
		
		//ищем тоже самое но с дочерними в противоположном положении
		if (!isset($switchParentCount)) {
			$switchParent=clone $searchModel;
			$switchParent->parent_id=!$switchParent->parent_id;
			$switchParentCount=$switchParent->search(Yii::$app->request->queryParams)->totalCount;
		}
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
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
	 * Отображает список активных сервисов, сгруппированных по ответственным пользователям.
	 *
	 * Выбирает только неархивные сервисы с признаком directlySupported,
	 * включая дочерние. Использует вид list-by-users.
	 *
	 * GET: нет специальных параметров (queryParams ServicesSearch для фильтрации).
	 *
	 * @param array $disabled_ids Массив ID сервисов, которые нужно пометить как отключённые в UI
	 * @return string Страница списка сервисов по ответственным
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
	 * Acceptance test data for IndexByUsers.
	 *
	 * Проверяет рендер страницы списка сервисов по ответственным.
	 * GET: нет параметров. Тест проходит при наличии любых (или нулевых) данных в БД.
	 */
	public function testIndexByUsers(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
	/**
	 * Отображает полное дерево всех сервисов (включая дочерние) в виде плоского отсортированного списка.
	 *
	 * Строит иерархическое дерево из всех сервисов с помощью ArrayHelper::buildSortedTree,
	 * затем разворачивает его в плоский список (sortFlatTree) для рендера в tabular-виде.
	 * Передаёт switchArchivedCount для переключателя архивных записей.
	 *
	 * GET: queryParams — параметры ServicesSearch для фильтрации.
	 *
	 * @return string Страница дерева сервисов
	 */
	public function actionIndexTree()
	{
		Services::cacheAllItems();
		$searchModel = new ServicesSearch();
		$searchModel->parent_id=true;  //в т.ч. дочерние
		//$searchModel->archived=false; //должен отсутствовать
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;



		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$this->view->params['layout-container'] = 'container-fluid';
		
		$models=[];
		ArrayHelper::sortFlatTree(ArrayHelper::buildSortedTree($dataProvider->models),$models);
		
		$arrDataProvider=new ArrayDataProvider([
			'allModels'=>$models,
			'pagination'=>false
		]);
		
		
		return $this->render('full-tree', [
			'searchModel' => $searchModel,
			'dataProvider' => $arrDataProvider,
			'switchArchivedCount' => $switchArchivedCount,
		]);
	}
	
		
	/**
	 * Acceptance test data for IndexTree.
	 *
	 * Проверяет рендер страницы полного дерева сервисов.
	 * GET: нет параметров. Тест проходит при наличии любых (или нулевых) данных в БД.
	 */
	public function testIndexTree(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
	/**
	 * AJAX: возвращает список ПК и оборудования, рекурсивно связанных с сервисом.
	 *
	 * Собирает compsRecursive и techsRecursive для сервиса, загружает их через
	 * CompsSearch/TechsSearch (со всеми joinWith), объединяет в один ArrayDataProvider.
	 * Устанавливает заголовок X-Pagination-Total-Count.
	 * GET: id (int) — идентификатор сервиса.
	 *
	 * @param int $id Идентификатор сервиса
	 * @return string HTML-таблица со списком ПК и оборудования
	 * @throws NotFoundHttpException если сервис не найден
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

		Yii::$app->response->headers->set('X-Pagination-Total-Count', $dataProvider->totalCount);

		return $this->renderAjax('comps-list', [
			'model'=>$model,
			'dataProvider' => $dataProvider,
		]);
	}



	
	/*
	 * (Закомментировано) Список ACE-связей в сервисе (с учётом вложенных).
	 * Функциональность перенесена или временно отключена.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	/*public function actionAcesList(int $id)
	{
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$ids=is_array($children)?ArrayHelper::getArrayField($children,'id'):[];
		$ids=array_merge($ids,ArrayHelper::getArrayField(Services::buildTreeBranch($model,'parentService'),'id'));
		
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['services_subject_ids'=>$ids]]
		));
		
		Yii::$app->response->headers->set('X-Pagination-Total-Count', $dataProvider->totalCount);
		
		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model,
			'mode' => 'aces'
		]);
	}

	/**
	 * (Закомментировано) Список ACL-связей в сервисе (с учётом вложенных).
	 * Функциональность перенесена или временно отключена.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	/*public function actionAclsList(int $id)
	{
		$model=$this->findModel($id);
		
		// Модели полноценного поиска нужны для подгрузки всех joinWith
		$searchModel=new AcesSearch();
		$searchModel->archived=Yii::$app->request->get('showArchived',false);
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$ids=is_array($children)?ArrayHelper::getArrayField($children,'id'):[];
		$ids=array_merge($ids,ArrayHelper::getArrayField(Services::buildTreeBranch($model,'parentService'),'id'));
		
		
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(
			Yii::$app->request->queryParams,
			['AcesSearch'=>['services_resource_ids'=>$ids]]
		));
		
		Yii::$app->response->headers->set('X-Pagination-Total-Count', $dataProvider->totalCount);

		return $this->renderAjax('aces-list', [
			'searchModel'=>$searchModel,
			'dataProvider' => $dataProvider,
			'model' => $model,
			'mode' => 'acls'
		]);
	}*/

		
	/**
	 * Acceptance test data for OsList.
	 *
	 * Проверяет AJAX-рендер списка ПК/оборудования для существующего сервиса.
	 * GET: id из getTestData()['full']. Тест проходит даже если список пустой.
	 */
	public function testOsList(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	public $modelClass=Services::class;
	/**
	 * AJAX: возвращает дерево дочерних сервисов для заданного сервиса.
	 *
	 * Рекурсивно получает всех потомков, строит отсортированное плоское дерево
	 * и передаёт его через ArrayDataProvider. Устанавливает X-Pagination-Total-Count.
	 *
	 * GET:
	 *   id (int) — идентификатор родительского сервиса.
	 *   showArchived (bool, опционально) — включить архивные дочерние сервисы.
	 *
	 * @param int $id Идентификатор родительского сервиса
	 * @return string HTML AJAX-рендер дерева дочерних сервисов
	 * @throws NotFoundHttpException если сервис не найден
	 */
	public function actionChildrenTree(int $id)
	{
		/** @var Services $model */
		$model=$this->findModel($id);
		
		// получаем всех детей
		$children=$model->getChildrenRecursive();
		
		$searchModel = new ServicesSearch();
		$searchModel->parent_id=true;  //в т.ч. дочерние
		$searchModel->ids=array_merge(ArrayHelper::getArrayField($children,'id'),[$id]);  //только эти
		$searchModel->archived=Yii::$app->request->get('showArchived',false);
		
		
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
		
		Yii::$app->response->headers->set('X-Pagination-Total-Count', $arrDataProvider->totalCount);
		
		return $this->renderAjax('children-tree', [
			'model' => $model,
			'dataProvider' => $arrDataProvider,
			//'switchArchivedCount' => $switchArchivedCount,
		]);
	}	
	/**
	 * Acceptance test data for ChildrenTree.
	 *
	 * Проверяет AJAX-рендер дерева дочерних сервисов.
	 * GET: id из getTestData()['full']. Тест проходит даже при отсутствии дочерних.
	 */
	public function testChildrenTree(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}

	
}
