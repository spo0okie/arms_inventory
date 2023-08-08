<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
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
	
	
	/**
	 * Что должен вернуть контроллер
	 * @param array $defaultPath путь куда вернуться если вызов не Ajax и не указано previous
	 * @param mixed $ajaxObject какой объект вернуть если вызов Ajax
	 * @param bool $previous признак что если в вызове есть return=previous то туда и возвращаемся
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
	
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
		$behaviors=[
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['POST'],
				],
			]
		];
		if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
			'class' => \yii\filters\AccessControl::className(),
			'rules' => [
				['allow' => true, 'actions'=>['create','update','delete'], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate',], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
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
				$searchModel->archived=\Yii::$app->request->get('showArchived',$this->defaultShowArchived);
			
			return $this->render('index', [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
			]);
			
		} else {
			$query=($this->modelClass)::find();
			$model= new $this->modelClass();
			if ($model->hasAttribute('archived')) {
				if (!Yii::$app->request->get('showArchived',$this->defaultShowArchived))
					$query->where(['not',['archived'=>1]]);
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
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem($id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
		]);
	}
	
	
	/**
	 * Displays a tooltip for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip($id)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
		]);
	}
	

	/**
	 * Displays a single Arms model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}

    /**
     * Validates  model on update.
     * @param null $id
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
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
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
			return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
		}
		
		$model->load(Yii::$app->request->get());
		return $this->defaultRender('create', ['model' => $model,]);
	}

	/**
     * Updates an existing model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
        }
	
		return $this->defaultRender('update', ['model' => $model,]);
    }



	/**
	 * Deletes an existing Arms model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

	    if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
        return $this->redirect(['index']);
    }

    /**
     * Finds the Arms model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ArmsModel the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ($this->modelClass)::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
