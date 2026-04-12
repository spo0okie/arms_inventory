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

	/**
	 * Возвращает набор тестовых данных для приёмочных тестов контроллера.
	 *
	 * Данные создаются через {@see ModelFactory::create()} и кешируются на время жизни теста.
	 * Каждый ключ массива соответствует конкретному сценарию:
	 *
	 *  - `'empty'`        — минимально заполненная модель (сохранена в БД); используется для проверки
	 *                       отображения при отсутствии данных (пустые поля, нет связей).
	 *  - `'full'`         — полностью заполненная модель (сохранена в БД); используется для проверки
	 *                       отображения всех атрибутов и связанных объектов.
	 *  - `'update'`       — минимальная модель (сохранена в БД), над которой выполняется тест обновления.
	 *  - `'update-data'`  — полностью заполненная модель (НЕ сохранена); её атрибуты используются
	 *                       как POST-данные при тесте обновления `'update'`.
	 *  - `'delete'`       — минимальная модель (сохранена в БД), которая будет удалена в testDelete().
	 *  - `'create'`       — полностью заполненная модель (НЕ сохранена); её атрибуты передаются
	 *                       как POST-данные при тесте создания новой записи.
	 *  - `'validate'`     — минимальная модель (сохранена в БД), для которой выполняется тест
	 *                       валидации существующей записи (передаётся её id в GET).
	 *  - `'validate-data'`— модель (НЕ сохранена); её атрибуты передаются как POST-данные
	 *                       в обоих сценариях testValidate() (новая и существующая запись).
	 *
	 * Результат кешируется в статическом свойстве `$testDataCache` по имени класса модели,
	 * чтобы не создавать дублирующиеся записи при многократных вызовах в рамках одного теста.
	 *
	 * @return array<string, \app\models\base\ArmsModel> карта тестовых моделей
	 */
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
	
	/**
	 * actions action.
	 */
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
     * Выводит страницу со списком моделей (UI: таблица/список объектов).
     *
     * Поведение:
     *  - Если существует класс `{ModelClass}Search`, использует его для поиска и фильтрации.
     *  - Если у поисковой модели есть атрибут/свойство `archived`, инициирует расширенный
     *    режим поиска с переключателем архивных записей через {@see archivedSearchInit()}.
     *  - Если кастомный шаблон `<controller>/index.php` существует — использует его,
     *    иначе — стандартный `/layouts/index`.
     *
     * GET-параметры:
     *  - `showArchived` (bool, опционально) — показывать ли архивные записи.
     *    Если не передан, используется значение по умолчанию `$defaultShowArchived`.
     *  - Любые параметры поисковой модели для фильтрации (передаются через `queryParams`).
     *  - `SearchOverride` (array, опционально) — перекрытие параметров поиска через
     *    {@see searchParamsOverride()}.
     *
     * @return string отрендеренный HTML страницы со списком
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
	 * Тест для {@see actionIndex()}: проверяет, что страница списка моделей открывается без ошибок.
	 *
	 * Сценарий:
	 *  - Перед вызовом через {@see getTestData()} создаются тестовые записи ('full' и 'empty'),
	 *    чтобы гарантировать непустой датасет и корректную работу шаблонов отрисовки.
	 *  - GET-запрос без параметров на URI action=index.
	 *  - Ожидаемый HTTP-ответ: 200.
	 *
	 * Особенности: параметр showArchived не передаётся — используется значение по умолчанию.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Асинхронный рендер только таблицы DynaGrid (без оболочки страницы).
	 *
	 * Используется DynaGridWidget для подгрузки содержимого таблицы через AJAX.
	 * Устанавливает заголовок ответа `X-Pagination-Total-Count` с общим числом записей.
	 * Если класс `{ModelClass}Search` не найден — бросает {@see NotFoundHttpException} (HTTP 404).
	 *
	 * GET-параметры:
	 *  - `source` (string, обязательный) — URL источника данных для DynaGrid (передаётся
	 *    в шаблон как параметр конфигурации виджета).
	 *  - `gridId` (string, опционально) — идентификатор DynaGrid; если не передан,
	 *    используется `{model-id}-list` (например, `comps-list`).
	 *  - `showArchived` (bool, опционально) — показывать ли архивные записи.
	 *  - Любые параметры поисковой модели для фильтрации.
	 *  - `SearchOverride` (array, опционально) — перекрытие параметров поиска.
	 *
	 * @param string $source URL источника данных для DynaGrid (из GET-параметра)
	 * @return string отрендеренный HTML фрагмента таблицы
	 * @throws NotFoundHttpException если класс поиска не найден
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
	 * Тест для {@see actionAsyncGrid()}: проверяет корректность асинхронного рендера таблицы.
	 *
	 * Сценарий:
	 *  - Через {@see getTestData()} создаются тестовые записи для непустого набора данных.
	 *  - GET-запрос с параметром `source=http://10.10.10.10` (произвольный URL-источник для DynaGrid).
	 *  - Ожидаемый HTTP-ответ: **200** (если у модели есть Search-класс) или **404**
	 *    (если Search-класс отсутствует — action бросает NotFoundHttpException).
	 *
	 * Особенности: `source` — обязательный параметр action; без него запрос завершится ошибкой
	 * на уровне роутинга до попадания в логику action.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Выводит короткую карточку (мини-представление) модели.
	 *
	 * Используется для отображения объекта в связанных списках, inline-блоках
	 * и всплывающих панелях без полной страницы просмотра.
	 * Если кастомный шаблон `<controller>/item.php` существует — использует его,
	 * иначе — стандартный `/layouts/item`.
	 * Всегда рендерит через {@see renderPartial()} без layout-обёртки.
	 *
	 * GET-параметры:
	 *  - `id` (int, обязательный) — первичный ключ модели.
	 *
	 * @param int $id первичный ключ модели
	 * @return string отрендеренный HTML карточки
	 * @throws NotFoundHttpException если модель не найдена
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
	 * Тест для {@see actionItem()}: проверяет рендер карточки для двух крайних случаев данных.
	 *
	 * Сценарии:
	 *  1. `'item full'`  — GET id={full->id}: карточка полностью заполненной модели.
	 *     Проверяет, что все атрибуты отрисовываются без ошибок. Ожидаемый ответ: 200.
	 *  2. `'item empty'` — GET id={empty->id}: карточка минимально заполненной модели.
	 *     Проверяет, что шаблон не падает при пустых/null полях. Ожидаемый ответ: 200.
	 *
	 * Обе модели создаются через {@see getTestData()} и сохраняются в БД,
	 * поэтому их id гарантированно существуют на момент теста.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Выводит короткую карточку модели, найденной по значению атрибута `name`.
	 *
	 * Предназначен для интеграции с wiki-синтаксисом и внешних систем, которые
	 * ссылаются на объекты по имени, а не по id.
	 * Логика поиска — через {@see findByName()}: ищет по полю `name`, бросает 404 если не найдено.
	 * Если кастомный шаблон `<controller>/item.php` существует — использует его,
	 * иначе — стандартный `/layouts/item`.
	 * Всегда рендерит через {@see renderPartial()} без layout-обёртки.
	 *
	 * GET-параметры:
	 *  - `name` (string, обязательный) — значение поля `name` искомой модели.
	 *
	 * @param string $name значение атрибута `name` модели
	 * @return string отрендеренный HTML карточки
	 * @throws NotFoundHttpException если объект с таким именем не найден
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
	 * Тест для {@see actionItemByName()}: проверяет поиск и рендер карточки по имени.
	 *
	 * Сценарии:
	 *  1. `'item by name full'`  — GET name={full->getName()}: поиск полностью заполненной модели.
	 *     Проверяет, что модель находится и карточка рендерится без ошибок.
	 *  2. `'item by name empty'` — GET name={empty->getName()}: поиск минимальной модели.
	 *     Проверяет корректную работу при минимальных данных.
	 *
	 * Особенности:
	 *  - Если `empty->getName()` возвращает пустую строку (модель без имени), второй сценарий
	 *    помечается как `skip=true` с причиной `'empty model has no name'`, поскольку
	 *    поиск по пустому имени лишён смысла и может вернуть неожиданный результат.
	 *  - Чтобы заменить skip на реальный тест: убедитесь, что ModelFactory генерирует непустое
	 *    значение поля `name` для empty-модели данного класса. После этого skip автоматически
	 *    не сработает — условие `empty($emptyName)` станет false.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Выводит tooltip-карточку модели (всплывающее представление).
	 *
	 * Используется для отображения краткой информации об объекте во всплывающих
	 * подсказках без перехода на страницу просмотра.
	 *
	 * Поведение:
	 *  - Если передан GET-параметр `timestamp` — ищет запись из журнала изменений
	 *    на момент указанного времени через {@see findJournalRecord()},
	 *    что позволяет отображать исторические версии объекта.
	 *  - Если `timestamp` не передан — загружает текущую версию модели через {@see findModel()}.
	 *  - Если кастомный шаблон `<controller>/ttip.php` существует — использует его,
	 *    иначе — стандартный `/layouts/ttip`.
	 *  - Всегда рендерит через {@see renderPartial()} без layout-обёртки.
	 *
	 * GET-параметры:
	 *  - `id` (int, обязательный) — первичный ключ модели.
	 *  - `timestamp` (int, опционально) — Unix-timestamp для отображения исторической
	 *    версии из журнала изменений.
	 *
	 * @param int $id первичный ключ модели
	 * @return string отрендеренный HTML tooltip-карточки
	 * @throws NotFoundHttpException если модель или запись журнала не найдена
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
	 * Тест для {@see actionTtip()}: проверяет рендер tooltip-карточки для двух крайних случаев.
	 *
	 * Сценарии:
	 *  1. `'ttip full'`  — GET id={full->id}: tooltip полностью заполненной модели.
	 *     Проверяет, что все атрибуты отображаются без ошибок. Ожидаемый ответ: 200.
	 *  2. `'ttip empty'` — GET id={empty->id}: tooltip минимально заполненной модели.
	 *     Проверяет устойчивость шаблона к пустым/null полям. Ожидаемый ответ: 200.
	 *
	 * Параметр `timestamp` не передаётся — тестируется только текущая версия модели.
	 * Обе модели создаются через {@see getTestData()} и сохраняются в БД.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Отображает полную страницу просмотра конкретной модели.
	 *
	 * Поведение:
	 *  - Загружает модель по id через {@see findModel()}, бросает 404 если не найдена.
	 *  - Если кастомный шаблон `<controller>/view.php` существует — использует его,
	 *    иначе — стандартный `/layouts/view`.
	 *  - Рендерит через {@see defaultRender()}: обычный render() или renderAjax()
	 *    в зависимости от типа запроса.
	 *
	 * GET-параметры:
	 *  - `id` (int, обязательный) — первичный ключ модели.
	 *
	 * @param int $id первичный ключ модели
	 * @return string отрендеренный HTML страницы просмотра
	 * @throws NotFoundHttpException если модель не найдена
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
	 * Тест для {@see actionView()}: проверяет рендер страницы просмотра для двух крайних случаев.
	 *
	 * Сценарии:
	 *  1. `'view full'`  — GET id={full->id}: страница просмотра полностью заполненной модели.
	 *     Проверяет отображение всех атрибутов и связей. Ожидаемый ответ: 200.
	 *  2. `'view empty'` — GET id={empty->id}: страница просмотра минимальной модели.
	 *     Проверяет устойчивость шаблона к пустым/null полям. Ожидаемый ответ: 200.
	 *
	 * Обе модели создаются через {@see getTestData()} и сохраняются в БД.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
     * Выполняет Ajax-валидацию данных модели без сохранения.
     *
     * Предназначен для проверки корректности полей формы на лету (inline-валидация
     * через {@see ArmsForm::validate()}).
     * Использует сценарий {@see ArmsModel::SCENARIO_VALIDATION}.
     * Всегда возвращает ответ в формате JSON.
     * Принимает только POST-запросы (см. VerbFilter в behaviors()).
     *
     * GET-параметры:
     *  - `id` (int, опционально) — если передан, загружает существующую модель по id
     *    и валидирует её с POST-данными; если не передан — создаёт новый экземпляр модели.
     *
     * POST-параметры:
     *  - Данные формы в формате `{ModelClass}[attribute]` — стандартный Yii2 load-формат.
     *    Если POST-данные не содержат ключ модели, метод возвращает null без валидации.
     *
     * @param int|null $id первичный ключ модели (опционально, для существующей записи)
     * @return array|null JSON-массив с ошибками валидации или null если данные не загружены
     * @throws NotFoundHttpException если id передан, но модель не найдена
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
	 * Тест для {@see actionValidate()}: проверяет Ajax-валидацию для новой и существующей модели.
	 *
	 * Сценарии:
	 *  1. `'validate new'`      — POST данные формы без GET-параметра id.
	 *     Валидирует новый экземпляр модели. Ожидаемый ответ: 200 (JSON с результатами валидации).
	 *  2. `'validate existing'` — POST данные формы + GET id={update->id}.
	 *     Валидирует существующую запись, загружая её из БД. Ожидаемый ответ: 200.
	 *
	 * POST-данные формируются через {@see ModelHelper::fillForm()} из модели `'validate-data'`
	 * (несохранённая полностью заполненная модель). Это гарантирует передачу корректных
	 * данных формы в формате, который ожидает {@see \yii\base\Model::load()}.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Отображает форму создания новой модели и обрабатывает её отправку.
	 *
	 * Поведение:
	 *  - GET-запрос: отображает пустую форму; предзаполняет модель из GET-параметров
	 *    через {@see \yii\base\Model::load()} (удобно для создания с предустановленными полями).
	 *  - POST-запрос: загружает данные формы, пытается сохранить модель.
	 *    При успехе:
	 *     - Если клиент принимает `application/json` — возвращает JSON с данными модели (REST).
	 *     - Иначе — перенаправляет через {@see defaultReturn()} на страницу просмотра
	 *       (или на предыдущую страницу, если передан `return=previous`).
	 *  - Если кастомный шаблон `<controller>/create.php` существует — использует его,
	 *    иначе — стандартный `/layouts/create`.
	 *  - Регистрирует {@see ArmsFormAsset} для подключения JS/CSS форм ARMS.
	 *
	 * GET-параметры:
	 *  - Любые атрибуты модели для предзаполнения формы (в формате `{ModelClass}[attr]` или плоском).
	 *  - `return` (string, опционально) — `'previous'` для редиректа на предыдущую страницу после создания.
	 *
	 * POST-параметры:
	 *  - Данные формы в формате `{ModelClass}[attribute]` — стандартный Yii2 load-формат.
	 *
	 * @return string|\yii\web\Response отрендеренный HTML формы или редирект/JSON после сохранения
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
	 * Тест для {@see actionCreate()}: проверяет загрузку формы и создание записи через POST.
	 *
	 * Сценарии:
	 *  1. `'form load'` — GET-запрос без параметров.
	 *     Проверяет, что форма создания открывается без ошибок. Ожидаемый ответ: 200.
	 *  2. `'form post'` — POST-запрос с данными формы из `'create'`-модели.
	 *     POST-данные формируются через {@see ModelHelper::fillForm()} из несохранённой
	 *     полностью заполненной модели (`$testData['create']`).
	 *     Ожидаемый ответ: 200 (рендер формы с ошибками) или 302 (редирект после успешного сохранения).
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
     * Отображает форму редактирования существующей модели и обрабатывает её отправку.
     *
     * Поведение:
     *  - GET-запрос: загружает модель по id, отображает форму редактирования;
     *    дополнительно применяет GET-параметры для предзаполнения полей через load().
     *  - POST-запрос: загружает данные формы, пытается сохранить модель.
     *    При успехе:
     *     - Если клиент принимает `application/json` — возвращает JSON с данными модели (REST).
     *     - Иначе — перенаправляет через {@see defaultReturn()} на страницу просмотра
     *       (или на предыдущую страницу, если передан `return=previous`).
     *  - Если кастомный шаблон `<controller>/update.php` существует — использует его,
     *    иначе — стандартный `/layouts/update`.
     *  - Регистрирует {@see ArmsFormAsset} для подключения JS/CSS форм ARMS.
     *
     * GET-параметры:
     *  - `id` (int, обязательный) — первичный ключ редактируемой модели.
     *  - `return` (string, опционально) — `'previous'` для редиректа на предыдущую страницу после сохранения.
     *  - `accept` (string, опционально) — если передан, после сохранения редиректит на update вместо view.
     *  - Любые атрибуты модели для предзаполнения полей формы.
     *
     * POST-параметры:
     *  - Данные формы в формате `{ModelClass}[attribute]` — стандартный Yii2 load-формат.
     *
     * @param int $id первичный ключ редактируемой модели
     * @return string|\yii\web\Response отрендеренный HTML формы или редирект/JSON после сохранения
     * @throws NotFoundHttpException если модель не найдена
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
	 * Тест для {@see actionUpdate()}: проверяет открытие формы редактирования и POST-обновление.
	 *
	 * Сценарии:
	 *  1. `'form open'` — GET id={update->id} без POST.
	 *     Проверяет, что форма редактирования существующей записи открывается без ошибок.
	 *     Ожидаемый ответ: 200.
	 *  2. `'data post'` — GET id={update->id} + POST данные формы из `'update-data'`-модели.
	 *     POST-данные формируются через {@see ModelHelper::fillForm()} из несохранённой
	 *     полностью заполненной модели (`$testData['update-data']`).
	 *     Ожидаемый ответ: 200 (рендер формы с ошибками) или 302 (редирект после успешного сохранения).
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
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
	 * Удаляет существующую модель.
	 *
	 * Принимает только POST-запросы (см. VerbFilter в behaviors()).
	 * После успешного удаления:
	 *  - Если в GET или POST передан параметр `return=previous` — перенаправляет
	 *    на предыдущую страницу (из Url::previous()).
	 *  - Иначе — перенаправляет по маршруту из {@see routeOnDelete()} (по умолчанию — index).
	 *
	 * GET-параметры:
	 *  - `id` (int, обязательный) — первичный ключ удаляемой модели.
	 *  - `return` (string, опционально) — `'previous'` для редиректа на предыдущую страницу.
	 *
	 * POST-параметры:
	 *  - `return` (string, опционально) — `'previous'` для редиректа на предыдущую страницу.
	 *  - Тело POST может быть пустым (достаточно самого факта POST-запроса).
	 *
	 * @param int $id первичный ключ удаляемой модели
	 * @return \yii\web\Response редирект после удаления
	 * @throws NotFoundHttpException если модель не найдена
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
	 * Тест для {@see actionDelete()}: проверяет удаление существующей записи через POST.
	 *
	 * Сценарий:
	 *  - `'default'` — GET id={delete->id} + пустой POST-тело.
	 *    Модель `'delete'` создаётся через {@see getTestData()} и сохраняется в БД специально
	 *    для последующего удаления в этом тесте.
	 *    Ожидаемый ответ: 302 (редирект на index после успешного удаления).
	 *
	 * Особенности: пустой POST (`[]`) необходим, так как action принимает только POST-запросы
	 * (VerbFilter). GET-параметр `id` передаётся отдельно через маршрут.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
	 */
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

	
	/**
	 * Тест для action `editable` (inline-редактирование через {@see EditableColumnAction}).
	 *
	 * Всегда возвращает skip-сценарий, поскольку inline-редактирование через Kartik Editable
	 * в текущей архитектуре ARMS не поддерживается и не тестируется.
	 *
	 * Особенности:
	 *  - `editable` action подключается через {@see actions()} как {@see EditableColumnAction},
	 *    но фактически не используется в UI ни одной из моделей.
	 *  - Чтобы заменить skip на реальный тест: необходимо реализовать поддержку
	 *    EditableColumnAction в конкретной модели/контроллере, после чего переопределить
	 *    этот метод в дочернем контроллере с реальными сценариями.
	 *
	 * @return array skip-сценарий с причиной пропуска
	 */
	public function testEditable(): array
	{
		return self::skipScenario('default', 'inline action is not supported');
	}

}
