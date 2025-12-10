<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\components\Forms\ArmsForm;
use app\components\Forms\assets\ArmsFormAsset;
use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\Users;
use kartik\grid\EditableColumnAction;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Базовый контроллер ARMS для всех моделей, наследуемых от ArmsModel.
 *
 * Назначение:
 * -----------
 * Этот класс реализует универсальный CRUD-контроллер, который:
 *  - обеспечивает типовое поведение для всех моделей ARMS;
 *  - автоматически использует стандартные view-файлы (`/layouts/*`), если
 *    в папке контроллера нет кастомных шаблонов;
 *  - реализует единый механизм отображения, валидации и обработки форм ARMS;
 *  - поддерживает синхронный и Ajax-режимы (render/renderAjax);
 *  - объединяет механику списков, карточек объекта, всплывающих подсказок (ttip),
 *    асинхронных таблиц (async-grid) и CRUD-операций.
 *
 * Требования к дочерним контроллерам:
 *  ----------------------------------
 *  - Контроллер обязан определить `$modelClass` - имя класса модели ARMS.
 *  - Все CRUD-действия будут работать автоматически, если не переопределены.
 *  - При необходимости можно отключить отдельные actions через disabledActions().
 *  - При наличии кастомного view файла (напр `<controller>/index.php` или `<controller>/view.php`)
 *    используется кастомный шаблон; иначе - стандартный `/layouts/*`.
 *
 * Доступ и авторизация:
 * ---------------------
 * Механизм доступа централизован через:
 *  - accessMap() - карта полномочий на actions (view/edit/view-class/edit-class);
 *  - buildAccessRules() - генерация правил Yii AccessControl;
 *  - HttpBasicAuth - поддержка HTTP Basic API-доступа;
 *  - параметр Yii::$app->params['useRBAC'] — интеграция с RBAC.
 *
 * Предусмотрены специальные полномочия:
 *  - PERM_ANONYMOUS      — доступ для гостей;
 *  - PERM_AUTHENTICATED  — доступ для авторизованных без явных прав;
 *  - PERM_VIEW / PERM_EDIT — глобальные права;
 *  - PERM_VIEW-<model> / PERM_EDIT-<model> — права на конкретные модели.
 *
 * Обработка запросов и навигация:
 * ------------------------------
 *  - defaultRender() — автоматически выбирает render()/renderAjax();
 *  - defaultReturn() — единый механизм “куда вернуть пользователя”
 *    после успешного create/update/delete, включая return=previous;
 *  - routeOnUpdate()/routeOnDelete() — точки расширения для навигации
 *    после CRUD-операций.
 *
 * Поддержка поиска и фильтрации:
 * ------------------------------
 *  - archivedSearchInit() — расширенный режим поиска с учётом архивных записей,
 *    переключателем и fallback-логикой при пустом результате;
 *  - searchParamsOverride() — механизм SearchOverride для подмены параметров
 *    поисковой модели, включая корректную маршрутизацию override'ов
 *    для разных Search-классов.
 *
 * Поддержка списков и таблиц:
 * ---------------------------
 *  - actionIndex()        — список объектов, с autodetect кастомных view;
 *  - actionAsyncGrid()    — асинхронный рендер DynaGrid с X-Pagination-Total-Count;
 *  - интеграция с DynaGridWidget и fetchVisibleAttributes().
 *
 * Поддержка чтения отдельных объектов:
 * ------------------------------------
 *  - actionItem() / actionItemByName() — рендер мини-карточки модели;
 *  - actionTtip() — рендер tooltip-представления (включая по журналу версий);
 *  - findModel(), findByName(), findJournalRecord() — централизованные
 *    методы загрузки данных, с корректными 404.
 *
 * Поддержка форм и API:
 * ---------------------
 *  - actionValidate() — Ajax-валидация модели (ArmsForm + SCENARIO_VALIDATION);
 *  - actionCreate()/actionUpdate() — автоподключение ArmsFormAsset,
 *    JSON-ответы для REST-клиентов;
 *
 * Поведение по умолчанию:
 * -----------------------
 * Контроллер реализует полный набор CRUD, готовый для использования
 * без создания шаблонов и без написания кода, если модель корректно
 * оформлена и наследует ArmsModel.
 *
 * Этот класс является обязательным базовым контроллером для всех CRUD
 * и определяет архитектурный подход ARMS к работе с моделями, формами,
 * поиском, листингами и доступами.
 */
class ArmsBaseController extends Controller
{
	
	/** @var string класс модели, операции с которой реализует контроллер */
	public $modelClass;
	
	/** @var bool показывать ли по умолчанию архивированные модели */
	public $defaultShowArchived=false;
	
	
	//как в карте доступов обозначать анонимный и авторизованный доступы
	const PERM_ANONYMOUS='@anonymous';
	const PERM_AUTHENTICATED='@authorized';
	const PERM_EDIT='edit';
	const PERM_VIEW='view';
	
	/**
	 * @var string HTML код, который будет добавлен на стандартную index страницу
	 * справа от (после) кнопки добавления/создания модели
	 * см views/layouts/index.php
	 */
	public $additionalCreateButton='';
	
	/**
	 * @var string HTML код, который будет добавлен на стандартную index страницу
	 * слева от (перед) кнопкой настроек либо перед переключателем архивных элементов (если он отображается)
	 * см views/layouts/index.php
	 */
	public $additionalToolButton='';
	
	
	public function actions()
	{
		return ArrayHelper::merge(parent::actions(), [
			'editable'=>[
				'class' => EditableColumnAction::class,		// action class name
				'modelClass' => $this->modelClass,			// the update model class
			],
		]);
	}
	
	/**
	 * Карта доступа с какими полномочиями, какие actions можно делать
	 * @return array
	 */
	public function accessMap() {
		$class=StringHelper::class2Id($this->modelClass);
		return [
			//чтение всего для полномочий view
			self::PERM_VIEW=>['index','view','search','ttip','item-by-name','item','async-grid'],
			//редактирование всего для полномочий edit
			self::PERM_EDIT=>['create','update','delete','validate','unlink','editable'],
			//чтение объектов этого класса для полномочий view-$class
			self::PERM_VIEW.'-'.$class=>['index','view','search','ttip','item-by-name','item','async-grid'],
			//редактирование объектов этого класса для полномочий edit-$class
			self::PERM_EDIT.'-'.$class=>['create','update','delete','validate','unlink'],
			//анонимный доступ по умолчанию ничего не разрешает
			self::PERM_ANONYMOUS=>[],
			//авторизованный доступ без явных полномочий по умолчанию ничего не разрешает
			self::PERM_AUTHENTICATED=>[],
		];
	}
	
	/**
	 * Что должен вернуть контроллер
	 * @param array $defaultPath путь куда вернуться если вызов не Ajax и не указано previous
	 * @param mixed $ajaxObject какой объект вернуть если вызов Ajax
	 * @param bool  $previous признак того, что в вызове есть return=previous и туда и надо возвращаться
	 * @return array|Response
	 */
	public function defaultReturn(array $defaultPath, $ajaxObject, $previous=true) {
		if (Yii::$app->request->isAjax) {

			Yii::$app->response->format = Response::FORMAT_JSON;
			return [$ajaxObject];

		}  elseif ($previous && Yii::$app->request->get('return')=='previous') {
			return $this->redirect(Url::previous());
		} else {
			return $this->redirect($defaultPath);
		}
	}
	
	/**
	 * Отрендерить страничку в обычном или Ajax режиме в зависимости от запроса
	 * @param      $path
	 * @param      $params
	 * @param array $ajaxParams
	 * @return string
	 */
	public function defaultRender($path,$params,$ajaxParams=[]) {
		//если параметры для режима Ajax не заданы, то те же что и для обычного
		if (empty($ajaxParams)) $ajaxParams=$params;
		
		//добавляем modalParent по умолчанию
		$ajaxParams=ArrayHelper::recursiveOverride(['modalParent' => '#modal_form_loader'],$ajaxParams);
		
		return Yii::$app->request->isAjax?
			$this->renderAjax($path,$ajaxParams):
			$this->render($path,$params);
	}
	
	/**
	 * Устанавливает один параметр запроса
	 * (из коробки только все одновременно можно установить - пришлось это дописать)
	 * @param $param
	 */
	public function setQueryParam($param)
	{
		$params=Yii::$app->request->getQueryParams();
		$newParams=ArrayHelper::recursiveOverride($params,$param);
		Yii::$app->request->setQueryParams($newParams);
	}
	
	/** @noinspection PhpUnusedParameterInspection */
	public static function buildAccessRules($map) {
		$rules=[];
		foreach ($map as $permission=>$actions) {
			$rule=['allow'=>true, 'actions'=>$actions];
			switch ($permission) {
				case self::PERM_AUTHENTICATED:
					$rule['roles']=['@'];
					break;
				case self::PERM_ANONYMOUS:
					$rule['roles']=['?'];
					break;
				/** @noinspection PhpMissingBreakStatementInspection */
				case 'view':
					//отрабатываем комбинацию !authorizedView && useRBAC дающую права просмотра всем аутентифицированным
					//https://wiki.reviakin.net/инвентаризация:настройка#авторизация
					if (
						empty(Yii::$app->params['authorizedView']) &&
						!empty(Yii::$app->params['useRBAC'])
					) $rule['roles']=['?'];
				default:
					$rule['permissions']=[$permission];
			}
			if (count($actions)) $rules[]=$rule;
		}
		return [
			'class' => AccessControl::class,
			'rules' => $rules,
			'denyCallback' => function ($rule, $action) {

				throw new  ForbiddenHttpException('Access denied');
			},
		];
	}
	
	/**
	 * Возвращает список отключенных методов
	 * (которые будут унаследованы, но отключены в дочерних классах)
	 * @return array
	 */
	public function disabledActions()
	{
		return [];
	}
	
	/**
     * @inheritdoc
     */
    public function behaviors()
    {
		$disabledActionsVerbs=[];
		foreach ($this->disabledActions() as $action) {
			$disabledActionsVerbs[$action]=[];
		};
		$behaviors=[
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => array_merge([
					'delete' => ['POST'],
					'validate' => ['POST'],
				],$disabledActionsVerbs),
			],
			'authenticator' => [
				'class' => HttpBasicAuth::class,
				'optional'=> ['*'],
				'auth' => function ($login, $password) {
					/** @var $user Users */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
			],
		];
		
		if (!empty(Yii::$app->params['useRBAC']))
			$behaviors['access']=static::buildAccessRules($this->accessMap());
		
		return $behaviors;
    }
	
	/**
	 * Ищет в переданном наборе параметров параметр SearchOverride, которым перекрывает
	 * параметры поиска в основном массиве
	 * если передано searchModel, то проверяет, что SearchOverride перекрывает именно атрибуты для этого класса
	 * @param array         $params
	 * @param object|string $searchModel
	 * @return array
	 */
	public function searchParamsOverride(array $params, object|string $searchModel=''): array
	{
		if (is_object($searchModel))
			$searchModel=StringHelper::className(get_class($searchModel));

		//если перекрытие параметров поиска есть
		if (isset($params['SearchOverride'])) {
			
			$override=$params['SearchOverride'];
			unset($params['SearchOverride']);
			
			//Если указано имя поисковой модели, и override его не содержит,
			//то складываем все параметры внутрь поискового класса, т.к. перекрыть надо именно его
			if ($searchModel && !isset($override[$searchModel]))
				$override=[$searchModel=>$override];
			
			$params=ArrayHelper::recursiveOverride(
				$params,
				$override
			);
		}
		
		return $params;
	}
	
	/**
	 * Инициирует поиск с учетом наличия переключателя архивных записей
	 * @param      $searchModel
	 * @param      $dataProvider
	 * @param      $switchArchivedCount
	 * @param string[]|null $columns
	 */
    public function archivedSearchInit(&$searchModel,&$dataProvider,&$switchArchivedCount,$columns=null,$params=null)
	{
		$searchModel->archived= Yii::$app->request->get('showArchived',$this->defaultShowArchived);
		
		//признак того, что свойства ниже указаны явно (не равны значениям по умолчанию)
		$direct_archived=Yii::$app->request->get('showArchived','unset')!='unset';
		
		if (is_null($params)) $params=Yii::$app->request->queryParams;
		
		$params=$this->searchParamsOverride($params,$searchModel);
		
		$dataProvider = $searchModel->search($params,$columns);
		if (!$dataProvider->totalCount) {
			if (!$direct_archived && !$searchModel->archived) {
				//если архивные неявно отключены, то смотрим что будет вместе с ними
				$switchArchived=clone $searchModel;
				$switchArchived->archived=!$switchArchived->archived;
				$switchArchivedData=$switchArchived->search($params,$columns);
				$switchArchivedCount=$switchArchivedData->totalCount;
				if ($switchArchivedCount) {
					//если есть архивные, то заменяем текущий поиск на поиск с архивными
					//и устанавливаем количество записей без архивных как альтернативное
					$switchArchivedCount=$dataProvider->totalCount;
					$searchModel=$switchArchived;
					$dataProvider=$switchArchivedData;
				}
			}
			
		}
		
		if (!isset($switchArchivedCount)) {
			//ищем то же самое, но с архивными в противоположном положении
			$switchArchived=clone $searchModel;
			$switchArchived->archived=!$switchArchived->archived;
			$switchArchivedCount=$switchArchived->search($params,$columns)->totalCount;
		}
		
	}
    
    /**
     * Lists all Arms models.
     * @return mixed
     */
    public function actionIndex()
    {
		$model= new $this->modelClass();
    	$searchModelClass=$this->modelClass.'Search';
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'index.php')?
			'index':'/layouts/index';
   
		$columns=DynaGridWidget::fetchVisibleAttributes($model,StringHelper::class2Id($this->modelClass).'-index');
		
    	if (class_exists($searchModelClass)) {
			$searchModel = new $searchModelClass();
			
			if ($searchModel->hasAttribute('archived') || $searchModel->canSetProperty('archived')) {
				$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns);
			} else {
				$dataProvider = $searchModel->search(Yii::$app->request->queryParams,$columns);
			}
			
			return $this->render($view, [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
				'switchArchivedCount' => $switchArchivedCount??null,
				'additionalCreateButton' => $this->additionalCreateButton,
				'additionalToolButton' => $this->additionalToolButton,
				'model' => $model,
			]);
			
		} else {
			$query=($this->modelClass)::find();
			if ($model->hasAttribute('archived')) {
				if (!Yii::$app->request->get('showArchived',$this->defaultShowArchived))
					$query->where(['not',['IFNULL(archived,0)'=>1]]);
			}
		
			$dataProvider = new ActiveDataProvider([
				'query' => $query,
				'pagination' => ['pageSize' => 100,],
			]);
			
			return $this->render($view, [
				'dataProvider' => $dataProvider,
				'model' => $model,
				'additionalCreateButton' => $this->additionalCreateButton,
				'additionalToolButton' => $this->additionalToolButton,
			]);
		}
    }
	
	/**
	 * Renders only Grid of index
	 * @return mixed
	 */
	public function actionAsyncGrid($source)
	{
		/** @var ArmsModel $model */
		$model= new $this->modelClass();
		
		$searchModelClass=$this->modelClass.'Search';
		$classId=StringHelper::class2Id($this->modelClass);
		$gridId=Yii::$app->request->get('gridId', $classId.'-list');
		
		$columns=DynaGridWidget::fetchVisibleAttributes($model,$gridId);
		
		if (class_exists($searchModelClass)) {
			/** @var ArmsModel $searchModel */
			$searchModel = new $searchModelClass();
			
			if ($searchModel->hasAttribute('archived') || $searchModel->canSetProperty('archived')) {
				$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns);
			} else {
				$dataProvider = $searchModel->search(
					$this->searchParamsOverride(Yii::$app->request->queryParams,$searchModel),
					$columns
				);
			}
			Yii::$app->response->headers->set('X-Pagination-Total-Count', $dataProvider->totalCount);
			return $this->renderAjax('/layouts/async-grid', compact('gridId','source','model','searchModel','dataProvider'));
			
		} else {
			throw new NotFoundHttpException("Search class $searchModelClass not found");
		}
	}

	/**
	 * Displays a item for single model.
	 * @param int  $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem(int $id)
	{
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'item.php')?
			'item':'/layouts/item';
		
		return $this->renderPartial($view, [
			'model' => $this->findModel($id),
			'static_view'=>true,
		]);
	}
	
	public function actionItemByName($name)
	{
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'item.php')?
			'item':'/layouts/item';

		return $this->renderPartial($view, [
			'model' => $this->findByName($name),
			'static_view'=>true,
		]);
	}
	
	/**
	 * Displays a tooltip for single model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip(int $id)
	{
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'ttip.php')?
			'ttip':'/layouts/ttip';
		
		if ($t=Yii::$app->request->get('timestamp')) {
			return $this->renderPartial($view, [
				'model' => $this->findJournalRecord($id,$t),
			]);
		}
		return $this->renderPartial($view, [
			'model' => $this->findModel($id),
		]);
	}
	
	
	/**
	 * Displays a single Arms model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView(int $id)
	{
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'view.php')?
			'view':'/layouts/view';
		return $this->defaultRender($view, [
			'model' => $this->findModel($id),
		]);
	}
	

    /**
     * Validates  model on update.
     * @param int|null $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionValidate($id=null)
    {
        if (!is_null($id))
            $model = $this->findModel($id);
        else
            $model = new $this->modelClass();

		$model->setScenario(ArmsModel::SCENARIO_VALIDATION);
        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ArmsForm::validate($model);
        }
        
        return null;
    }
	
	/**
	 * Маршрут куда идти при успешном сохранении, создании
	 * @param $model
	 * @return array
	 */
    public function routeOnUpdate($model) {
    	return [
    		Yii::$app->request->get('accept')?'update':'view',
			'id'=>$model->id,
		];
	}
	
	/**
	 * Маршрут куда идти при успешном удалении
	 * @param $model
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function routeOnDelete($model) {
		return ['index'];
	}
	
	
	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		$model = new $this->modelClass();

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (isset(Yii::$app->request->acceptableContentTypes['application/json'])) {
				Yii::$app->response->format=Response::FORMAT_JSON;
				return $model;
			}
			return $this->defaultReturn($this->routeOnUpdate($model),[$model]);
		}
		
		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'create.php')?
			'create':'/layouts/create';
		
		$model->load(Yii::$app->request->get());
		return $this->defaultRender($view, ['model' => $model,]);
	}

	/**
     * Updates an existing model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id)
    {
		$this->view->registerAssetBundle(ArmsFormAsset::class);
        $model = $this->findModel($id);

		$view=is_file($this->getViewPath().DIRECTORY_SEPARATOR.'update.php')?
			'update':'/layouts/update';

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
        	if (in_array('application/json',Yii::$app->request->acceptableContentTypes)) {
        		Yii::$app->response->format=Response::FORMAT_JSON;
        		return $model;
			}
			return $this->defaultReturn($this->routeOnUpdate($model),[
				$model,
			]);
        }

		$model->load(Yii::$app->request->get());
		return $this->defaultRender($view, ['model' => $model,]);
    }
	
	
	/**
	 * Deletes an existing Arms model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	$model=$this->findModel($id);
    	$defaultRoute=$this->routeOnDelete($model);
        $model->delete();
		$url=Url::previous();
	    if (
	    	Yii::$app->request->get('return')=='previous'
			||
			Yii::$app->request->post('return')=='previous'
		) return $this->redirect($url);
        return $this->redirect($defaultRoute);
    }
	
	/**
	 * Возвращает класс модели по имени
	 * @param $class
	 * @return string
	 * @throws NotFoundHttpException
	 *
	 */
	public static function findClass($class)
	{
		if (!str_contains($class,'\\')) {
			$class="app\\models\\$class";
		}
		
		if (!class_exists($class)) {
			throw new NotFoundHttpException("Class $class not found");
		}
		
		return $class;
	}
	
	public static function findClassModel($class,$id)
	{
		$class=static::findClass($class);

		/** @var $class ArmsModel */
		if (($model = ($class)::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException("$class [$id] does not exist.");
	}
	
	/**
	 * Finds the Arms model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param int $id
	 * @return ArmsModel the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel(int $id)
	{
		return static::findClassModel($this->modelClass,$id);
	}
	
	protected function findByName(string $name)
	{
		if (($model = ($this->modelClass)::find()->where(['name'=>$name])->one()) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('Object with requested name does not exist.');
	}
	

	/**
	 * Finds the Arms model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param int $id
	 * @param     $timestamp
	 * @return ArmsModel the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findJournalRecord(int $id, $timestamp)
	{
		if (($model = ($this->modelClass)::fetchJournalRecord($id,$timestamp)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
}
