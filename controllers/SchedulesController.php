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
use yii\web\Response;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class SchedulesController extends ArmsBaseController
{
	public $modelClass='app\models\Schedules';
	
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
	 * Lists only ACL Schedules.
	 * @return mixed
	 */
	public function actionIndexAcl()
	{
		Services::cacheAllItems();
		Places::cacheAllItems();
		$searchModel = new SchedulesSearchAcl();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('index-acl', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
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
		
		$support_service=null;
		$service=null;
		$item=null;

		//
		if (Yii::$app->request->get('attach_service')) {
			$service=\app\models\Services::findOne(Yii::$app->request->get('attach_service'));
			if (is_object($service)) {
				$model->name=\app\models\Schedules::$title.' работы '.$service->name;
			}
		} elseif (Yii::$app->request->get('support_service')) {
			$support_service=\app\models\Services::findOne(Yii::$app->request->get('support_service'));
			if (is_object($service)) {
				$model->name=\app\models\Schedules::$title.' поддержки '.$support_service->name;
			}
		}

		$model->load(Yii::$app->request->get());
		
		if ($model->override_id) {
			$model->parent_id = $model->override_id;
			$model->start_date = date('Y-m-d');
			$model->name='Override for #'.$model->override_id;
		}

		if ($model->load(Yii::$app->request->post())) {
			if ($model->save()) {
				//если было указано расписание по умолчанию - надо его создат в БД
				if (strlen($model->defaultItemSchedule)) {
					$item=new SchedulesEntries();
					$item->schedule=$model->defaultItemSchedule;
					$item->date='def';
					$item->schedule_id = $model->id;
					$item->save();
				}
				//если надо привязать сервис
				if (is_object($service)) {
					$service->providing_schedule_id = $model->id;
					$service->save();
					return $this->defaultReturn(['services/view', 'id' => $service->id],[$model]);
				} elseif (is_object($support_service)) { //или поддержку сервиса
					$support_service->providing_schedule_id = $model->id;
					$support_service->save();
					return $this->defaultReturn(['services/view', 'id' => $support_service->id],[$model]);
				} else
					return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
			}
		}
		
		
		
		return $this->defaultRender('create',[
			'model' => $model,
			'attach_service'=>Yii::$app->request->get('attach_service')
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
    	$model=$this->findModel($id);
    	$parent=$model->parent_id;
        $this->findModel($id)->delete();

        return $this->redirect($parent?['view', 'id' => $parent]:['index']);
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
