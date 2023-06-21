<?php

namespace app\controllers;

use app\models\Places;
use Yii;
use app\models\Services;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\ServicesSearch;

/**
 * ServicesController implements the CRUD actions for Services model.
 */
class ServicesController extends Controller
{
    /**
     * {@inheritdoc}
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
			    ['allow' => true, 'actions'=>['create','update','delete','unlink'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','index-by-users','view','card','card-support','ttip','validate','item','json-preview','os-list','techs-list'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
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
	 * Displays model fields in JSON.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionJsonPreview($id)
	{
		$model=$this->findModel($id);
		$response=[];
		foreach ($model->extraFields() as $field) {
			$response[$field]=$model->$field;
		}
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $response;
	}
	
	/**
	 * Displays a card for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionCard($id)
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
	public function actionCardSupport($id)
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
	 * @return mixed
	 */
	public function actionIndexByUsers()
	{
		Services::cacheAllItems();
		$searchModel = new ServicesSearch();
		$searchModel->directlySupported=true;
		$searchModel->archived=false; //должен отсутствовать
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$this->view->params['layout-container'] = 'container-fluid';
		
		return $this->render('list-by-users', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	
	
	/**
	 * Displays a single Services model.
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
	 * Список ОС рекурсивно задействованные в сервисе (с учетом вложенных)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionOsList($id)
	{
		$model=$this->findModel($id);
		//$comps=$model->compsRecursive;
		$dataProvider=new ArrayDataProvider([
			'allModels' => $model->compsRecursive,
			'key'=>'id',
			/*'sort' => [
				'attributes'=> [
					'objName',
					'comment',
					'changedAt',
					'changedBy',
				],
				'defaultOrder' => [
					'objName' => SORT_ASC
				]
			],*/
			'pagination' => false,
		]);;
		return $this->renderAjax('comps-list', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	
	/**
     * Creates a new Services model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Services();
	
		if (Yii::$app->request->get('parent_id'))
			$model->parent_id=Yii::$app->request->get('parent_id');

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(Url::previous());
		}
	
		$model->load(Yii::$app->request->get());
		
		return Yii::$app->request->isAjax?
			$this->renderAjax('create', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('create', [
				'model' => $model,
			]);
    }

    /**
     * Updates an existing Services model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(Url::previous());
            //return $this->redirect(['view', 'id' => $model->id]);
        }
	
		return Yii::$app->request->isAjax?
			$this->renderAjax('update', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('update', [
				'model' => $model,
			]);
    }
	
	/**
	 * Deletes an existing Services model.
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
		return $this->redirect(Url::previous());

        //return $this->redirect(['index']);
    }

    /**
     * Finds the Services model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Services the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Services::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
