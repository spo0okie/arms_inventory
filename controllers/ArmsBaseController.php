<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\components\Forms\ArmsForm;
use app\components\Forms\assets\ArmsFormAsset;
use app\generation\ModelFactory;
use app\helpers\ArrayHelper;
use app\helpers\ModelHelper;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
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
use yii\web\UnauthorizedHttpException;

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
	const PERM_EVERYONE='@everyone';
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

	static protected $testDataCache=[];
	public function getTestData() {
		$class=$this->modelClass;
		if (empty(static::$testDataCache[$class])) {
			//пустая модель для проверки отображения при отсутствии данных
			static::$testDataCache[$class]['empty']=		ModelFactory::create($class,['empty'=>true]);
			//полностью заполненная модель для проверки отображения всех данных
			static::$testDataCache[$class]['full']=			ModelFactory::create($class,['empty'=>false]);
			//какую модель обновлять
			static::$testDataCache[$class]['update']=		ModelFactory::create($class,['empty'=>true]);
			static::$testDataCache[$class]['update-data']=	ModelFactory::create($class,['empty'=>false,'save'=>false]);
			//какую модель удалять
			static::$testDataCache[$class]['delete']=		ModelFactory::create($class,['empty'=>true]);
			//данные для теста создания модели
			static::$testDataCache[$class]['create']=		ModelFactory::create($class,['empty'=>false,'save'=>false]);
			//данные для теста валидации модели
			static::$testDataCache[$class]['validate']=		ModelFactory::create($class,['empty'=>true]);
			static::$testDataCache[$class]['validate-data']=ModelFactory::create($class,['save'=>false]);
		}
		return static::$testDataCache[$class];
	}
	
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
	
	/**
	 * Корректирует роль доступа в зависимости от настроек приложения
	 * согласно https://wiki.reviakin.net/инвентаризация:настройка#авторизация
	 * @param string $permission
	 * @return string
	 */
	public static function customizeAccessPermission(string $permission):string {
		//анонимный доступ не меняем, он в любой ситуации для всех
		if ($permission===self::PERM_ANONYMOUS || $permission===self::PERM_EVERYONE) return $permission;
		//если разграничение прав отключено, то
		if (empty(Yii::$app->params['useRBAC'])) {
			//в зависимости от требования "авторизации для просмотра" все будет доступно
			return (Yii::$app->params['authorizedView']??false)?
				self::PERM_AUTHENTICATED:	//авторизованным пользователям
				self::PERM_EVERYONE;		//анонимным пользователям
		} else {
			//разграничение прав включено
			if (//но если
				empty(Yii::$app->params['authorizedView']) &&	//для просмотра не нужно авторизовываться
				str_starts_with($permission,'view')				//и мы как раз рассматриваем права на просмотр
			) return self::PERM_EVERYONE;					//то разрешаем всем авторизованным
		}
		return $permission;
	}
	
	/**
	 * Принимает на вход упрощенную карту вида [
	 * 		'permission1'=>['action1','action2',...],
	 * 		'permission2'=>['action4','action5',...],
	 * ] а отдает правила доступа для AccessControl, которые можно вставлять в behaviors[access]
	 * @param $map
	 * @return array
	 */
	public static function buildAccessRules($map) {
		$rules=[];
		foreach ($map as $permission=>$actions) {
			$rule=['allow'=>true, 'actions'=>$actions];
			
			$permission=static::customizeAccessPermission($permission);
			
			switch ($permission) {
				case self::PERM_AUTHENTICATED:
					$rule['roles']=['@'];
					break;
				case self::PERM_ANONYMOUS:
					$rule['roles']=['?'];
					break;
				case self::PERM_EVERYONE:
					$rule['roles']=['?','@'];
					break;
				default:
					$rule['permissions']=[$permission];
			}
			if (count($actions)) $rules[]=$rule;
		}
		return [
			'class' => AccessControl::class,
			'rules' => $rules,
			'denyCallback' => function ($rule, $action) {
				if (\Yii::$app->user->isGuest)
					throw new  UnauthorizedHttpException('Unauthorized access');
				else
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
	 * Возвращает список отключенных тестов
	 * по умолчанию отключает тесты для всех отключенных методов
	 * @return array
	 */
	public function disabledTests(): array
	{
		return $this->disabledActions();
	}

	/**
	 * Возвращает вместо списка маршрутов для тестов
	 * один маршрут-пропуск с указанием причины, по которой тесты отключены
	 * @param array $testData
	 * @return array|null
	 */
	protected static function skipScenario(string $name, string $reason): array
	{
		return [['name' => $name,'skip' => true,'reason' => $reason]];
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
					/** @var Users $user */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
			],
		];
		
		//if (!(empty(Yii::$app->params['useRBAC']) && empty(Yii::$app->params['authorizedView'])))
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
     * Вывести страницу со списком моделей
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
	 * Тест для страницы со списком моделей: вызывать URI без параметров и убедиться что путь открывается с кодом 200
	 * @return array
	 */
	public function testIndex(): array
	{
		//создаем несколько моделей, чтобы страница работала в сценарии когда что-то рендерится
		$this->getTestData();
		//один тест на открытие страницы, без параметров, с кодом 200
		return [[
			'name' => 'view index',
			'response' => 200,
		]];
	}
	
	
	/**
	 * Как index, но для асинхронного рендера только самой таблицы (через DynaGridWidget)
	 * @return mixed
	 */
	public function actionAsyncGrid($source)
	{
		/** @var \app\models\base\ArmsModel $model */
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
			return $this->renderAjax('//layouts/async-grid', compact('gridId','source','model','searchModel','dataProvider'));
			
		} else {
			throw new NotFoundHttpException("Search class $searchModelClass not found");
		}
	}
	
	/**
	 * Тест для асинхронной таблицы: вызывать URI без параметров и убедиться что путь открывается с кодом 200 или 404
	 * @return array
	 */
	public function testAsyncGrid(): array
	{
		//создаем несколько моделей, чтобы страница работала в сценарии когда что-то рендерится
		$this->getTestData();
		return [[
			'name' => 'async grid',
			'GET' => ['source' => 'http://10.10.10.10'],
			'response' => [404,200],	//для моделей без Search класса будет 404, для моделей с Search классом будет 200
		]];
	}

	/**
	 * Вывести короткое представление модели для отображения в списках, связанных объектах и т.п.
	 * @param int $id ID модели
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
	

	/**
	 * проверяем, что может отобразить как модель полностью заполненную, так и модель с минимальным набором данных
	 * оба варианта должны без ошибок отобразиться с кодом 200
	 */
	public function testItem(): array
	{
		$testData=$this->getTestData();
		$full=	$testData['full'];
		$empty=	$testData['empty'];
		return [
			['name' => 'item full',  'GET' => ['id' => $full->id],  'response' => 200,],
			['name' => 'item empty', 'GET' => ['id' => $empty->id], 'response' => 200,],
		];
	}
	
	/**
	 * Вывести короткое представление модели по имени модели (для интеграции с wiki синтаксисом)
	 */
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
	 * проверяем, что метод находит и отображает модель по имени, 
	 * как для полностью заполненной модели, так и для модели с минимальным набором данных
	 */
	public function testItemByName(): array
	{
		$testData=$this->getTestData();
		$full=	$testData['full'];
		$empty=	$testData['empty'];
		$emptyName=$empty->getName();
		return [
			[
				'name' => 'item by name full',	
				'GET' => ['name' => $full->getName()],	
			],
			[
				'name' => 'item by name empty',
				'GET' => ['name' => $emptyName],
				'skip' => empty($emptyName),		//если имя пустой модели тоже пустое, то тест бессмысленный, пропускаем его
				'reason' => 'empty model has no name',
			],
		];
	}
	
	/**
	 * Вернуть tooltip карточку модели
	 * Если передан параметр timestamp, то искать не текущую модель, а запись из журнала изменений модели на момент времени timestamp
	 * (для отображения истории изменений модели по журналу)
	 * Если шаблон ttip.php есть в папке контроллера, то использовать его, если нет, то использовать стандартный /layouts/ttip
	 * (для возможности кастомизации отображения tooltip для конкретной модели)
	 * @param int $id ID модели
	 * @param int|null $timestamp
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
	 * проверяем, что метод находит и отображает tooltip для модели, 
	 * как для полностью заполненной модели, так и для модели с минимальным набором данных
	 */
	public function testTtip(): array
	{
		$testData=$this->getTestData();
		$full=	$testData['full'];
		$empty=	$testData['empty'];
		return [
			['name' => 'ttip full',	 'GET' => ['id' => $full->id],	'response' => 200,],
			['name' => 'ttip empty', 'GET' => ['id' => $empty->id], 'response' => 200,],
		];
	}
	
	/**
	 * Отображает страницу просмотра конкретной модели
	 * @param int $id ID модели
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
	 * Проверяем, что страница просмотра модели открывается и отображает модель, как для полностью заполненной модели, 
	 * так и для модели с минимальным набором данных
	 */
	public function testView(): array
	{
		$testData=$this->getTestData();
		$full=	$testData['full'];
		$empty=	$testData['empty'];
		return [
			['name' => 'view full',	 'GET' => ['id' => $full->id],	'response' => 200,],
			['name' => 'view empty', 'GET' => ['id' => $empty->id],'response' => 200,],
		];
	}

    /**
     * Проверяем валдиность данных модели через Ajax, не сохраняя модель
     * @param int|null $id ID модели, если передан, то валидируем существующую модель, если не передан, то валидируем новую модель
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
	 * Тест для Ajax-валидации модели: отправляем данные для валидации и проверяем, что ответ приходит с кодом 200
	 * для валидации новой модели и для валидации существующей модели
	 * (для существующей модели передаем id модели в GET параметре, для новой модели не передаем id модели) 
	 */
	public function testValidate(): array
	{
		$testData=$this->getTestData();
		$model=$testData['update'];
		$data=$testData['validate-data'];
		return [
			['name' => 'validate new','POST' => ModelHelper::fillForm($data)],
			['name' => 'validate existing','POST' => ModelHelper::fillForm($data),'GET' => ['id' => $model->id],],
		];
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
	 * Создает новую модель. 
	 * Если данные переданы через POST, то пытается создать модель с этими данными, 
	 * если данные не переданы, то отображает форму создания модели.
	 * Если модель успешно создана, то 
	 *   - возвращает JSON с данными модели для REST-клиентов,
	 *   - или перенаправляет на страницу просмотра модели для обычных клиентов, если в запросе нет параметра return=previous,
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
	 * Тест для создания модели: открываем страницу создания модели и отправляем данные для создания модели, 
	 * проверяя, что в обоих случаях приходит код 200 или 201 или 302
	 */
	public function testCreate(): array
	{
		$testData=$this->getTestData();
		$model=$testData['create'];
		return [
			['name' => 'form load'],
			[
				'name' => 'form post',
				'POST'=>ModelHelper::fillForm($model),
				'response' => [200,302],
			],
		];
	}
	
	
	/**
     * Обновляет существующую модель.
	 * Если данные переданы через POST, то пытается обновить модель с этими данными,
	 * если данные не переданы, то отображает форму редактирования модели.
     * @param int $id ID модели
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
	 * Тест для обновления модели: открываем страницу редактирования модели и отправляем данные для обновления модели, 
	 * проверяя, что в обоих случаях приходит код 200, 202 или 302
	 */
	public function testUpdate(): array
	{
		$testData=$this->getTestData();
		$update=$testData['update'];
		$updateData=$testData['update-data'];
		return [
			['name' => 'form open','GET' => ['id' => $update->id]],
			[
				'name' => 'data post',
				'GET' => ['id' => $update->id],
				'POST' => ModelHelper::fillForm($updateData),
				'response' => [200,302],
			]
		];
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
	
	public function testDelete(): array
	{
		$testData=$this->getTestData();
		$delete=$testData['delete'];
		return [[
			'name' => 'default',
			'GET' => ['id' => $delete->id],
			'POST' => [],
			'response' => 302,
		]];
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

		/** @var $class \app\models\base\ArmsModel */
		if (($model = ($class)::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException("$class [$id] does not exist.");
	}
	
	/**
	 * Finds the Arms model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param int $id
	 * @return \app\models\base\ArmsModel the loaded model
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
	 * @return \app\models\base\ArmsModel the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findJournalRecord(int $id, $timestamp)
	{
		if (($model = ($this->modelClass)::fetchJournalRecord($id,$timestamp)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}

	
	public function testEditable(): array
	{
		return self::skipScenario('default', 'inline action is not supported');
	}

}
