<?php

namespace app\controllers;

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
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap5\ActiveForm;
use yii\web\Response;
use yii\helpers\Url;

/**
 * ArmsController implements the CRUD actions for Arms model.
 */
class ArmsBaseController extends Controller
{
	public $modelClass;
	public $defaultShowArchived=false;
	
	
	//как в карте доступов обозначать анонимный и авторизованный
	const PERM_ANONYMOUS='@anonymous';
	const PERM_AUTHENTICATED='@authorized';
	const PERM_EDIT='edit';
	const PERM_VIEW='view';
	
	
	public function actions()
	{
		return ArrayHelper::merge(parent::actions(), [
			'editable'=>[
				'class' => EditableColumnAction::class,		// action class name
				'modelClass' => $this->modelClass,			// the update model class
			]
		]);
	}
	
	/**
	 * Карта доступа с какими полномочиями что можно делать
	 * @return array
	 */
	public function accessMap() {
		$class=StringHelper::class2Id($this->modelClass);
		return [
			self::PERM_VIEW=>['index','view','search','ttip','item-by-name','item'],			//чтение всего
			self::PERM_EDIT=>['create','update','delete','validate','unlink','editable'],	//редактирование всего
			self::PERM_VIEW.'-'.$class=>['index','view','search','ttip','item-by-name','item'],	//чтение объектов этого класса
			self::PERM_EDIT.'-'.$class=>['create','update','delete','validate','unlink'],		//редактирование объектов этого класса
			self::PERM_ANONYMOUS=>[],
			self::PERM_AUTHENTICATED=>[],
		];
	}
	
	/**
	 * Что должен вернуть контроллер
	 * @param array $defaultPath путь куда вернуться если вызов не Ajax и не указано previous
	 * @param mixed $ajaxObject какой объект вернуть если вызов Ajax
	 * @param bool  $previous признак что если в вызове есть return=previous то туда и возвращаемся
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
	 * @param null $ajaxParams
	 * @return string
	 */
	public function defaultRender($path,$params,$ajaxParams=null) {
		//если параметры для режима Ajax не заданы, то те же что и для обычного
		if (is_null($ajaxParams)) $ajaxParams=$params;
		
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
				//$user=is_object(\Yii::$app->user->identity)?\Yii::$app->user->identity->Login:'anon';
				//error_log("{$action->id} denied for user $user");
				//if (Yii::$app->request->isAjax) return $this->defaultReturn('site/error',[]);
				throw new  ForbiddenHttpException('Access denied');
			}
		];
	}
	
	/**
     * @inheritdoc
     */
    public function behaviors()
    {
		$behaviors=[
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'delete' => ['POST'],
				],
			],
			'authenticator' => [
				'class' => HttpBasicAuth::class,
				'optional'=> /*(
					Yii::$app->user->isGuest &&	(
						!empty(Yii::$app->params['authorizedView']) ||
						!empty(Yii::$app->params['useRBAC'])
					)
				)?[]:*/['*'],
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
     * Lists all Arms models.
     * @return mixed
     */
    public function actionIndex()
    {
    	$searchModelClass=$this->modelClass.'Search';
    	
    	if (class_exists($searchModelClass)) {
			$searchModel = new $searchModelClass();
			$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
			
			if ($searchModel->hasAttribute('archived'))
				$searchModel->archived= Yii::$app->request->get('showArchived',$this->defaultShowArchived);
			
			return $this->render('index', [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
			]);
			
		} else {
			$query=($this->modelClass)::find();
			$model= new $this->modelClass();
			if ($model->hasAttribute('archived')) {
				if (!Yii::$app->request->get('showArchived',$this->defaultShowArchived))
					$query->where(['not',['IFNULL(archived,0)'=>1]]);
			}
		
			$dataProvider = new ActiveDataProvider([
				'query' => $query,
				'pagination' => ['pageSize' => 100,],
			]);
			
			return $this->render('index', [
				'dataProvider' => $dataProvider,
			]);
		}
    }
	
	/**
	 * Displays a item for single model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem(int $id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
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
		return $this->renderPartial('ttip', [
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
		return $this->defaultRender('view', [
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

        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
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
			'id'=>$model->id
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
		$model = new $this->modelClass();

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (isset(Yii::$app->request->acceptableContentTypes['application/json'])) {
				Yii::$app->response->format=Response::FORMAT_JSON;
				return $model;
			}
			return $this->defaultReturn($this->routeOnUpdate($model),[$model]);
		}
		
		$model->load(Yii::$app->request->get());
		return $this->defaultRender('create', ['model' => $model,]);
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
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
        	if (in_array('application/json',Yii::$app->request->acceptableContentTypes)) {
        		Yii::$app->response->format=Response::FORMAT_JSON;
        		return $model;
			}
			return $this->defaultReturn($this->routeOnUpdate($model),[
				$model
			]);
        }

		$model->load(Yii::$app->request->get());
		return $this->defaultRender('update', ['model' => $model,]);
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
     * Finds the Arms model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return ArmsModel the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id)
    {
        if (($model = ($this->modelClass)::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
