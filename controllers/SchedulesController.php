<?php

namespace app\controllers;

use app\models\SchedulesSearchAcl;
use Yii;
use app\models\Schedules;
use app\models\SchedulesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class SchedulesController extends Controller
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
				['allow' => true, 'actions'=>['create','create-acl','update','delete',], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','index-acl','view','ttip','validate'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
    }
	
	/**
	 * Lists all Schedules models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel = new SchedulesSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	
	/**
	 * Lists only ACL Schedules.
	 * @return mixed
	 */
	public function actionIndexAcl()
	{
		$searchModel = new SchedulesSearchAcl();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index-acl', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	
	/**
	 * Displays a single model ttip.
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
	 * Displays a single Schedules model.
	 * @param integer $id
	 * @param bool    $acl_mode
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    public function actionView($id,$acl_mode=false)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'acl_mode' => $acl_mode,
        ]);
    }
	
	/**
	 * Creates a new Schedules model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new Schedules();
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->id]);
		}
		
		return $this->render('create', [
			'model' => $model,
		]);
	}
	/**
	 * Creates a new Schedules model with attached ACL.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreateAcl()
	{
		$model = new Schedules();
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			$acl=new \app\models\Acls();
			$acl->schedules_id=$model->id;
			$acl->save();
			return $this->redirect(['view', 'id' => $model->id, 'acl_mode'=>1] );
		}
		
		return $this->render('create', [
			'model' => $model,
			'acl_mode'=>true
		]);
	}
	
	
	/**
     * Updates an existing Schedules model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Schedules model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Schedules model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Schedules the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Schedules::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
