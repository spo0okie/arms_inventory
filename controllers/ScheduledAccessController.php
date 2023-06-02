<?php

namespace app\controllers;

use app\models\Places;
use app\models\SchedulesEntries;
use app\models\SchedulesSearchAcl;
use app\models\Services;
use Yii;
use app\models\Schedules;
use app\models\SchedulesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class ScheduledAccessController extends ArmsController
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
				['allow' => true, 'actions'=>['create','update','delete',], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate','status'], 'roles'=>['@','?']],
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
		Services::cacheAllItems();
		Places::cacheAllItems();
		$searchModel = new SchedulesSearchAcl();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index', [
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
	 * Displays a single model status
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionStatus($id)
	{
		$model=$this->findModel($id);
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+Yii::$app->params['schedulesTZShift'])
		);
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
    	$model=$this->findModel($id);
    	if ($model->isOverride) {
    		$params=Yii::$app->request->get();
    		$params['id']=$model->override_id;
    		return $this->redirect(array_merge(['view'],$params));
		}
    		
        return $this->render('view', [
            'model' => $model,
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
			$acl=new \app\models\Acls();
			$acl->schedules_id=$model->id;
			$acl->save();
			return $this->redirect(['view', 'id' => $model->id, 'acl_mode'=>1] );
		}
		
		return $this->render('create', [
			'model' => $model,
		]);
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
