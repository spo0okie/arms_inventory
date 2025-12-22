<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\Acls;
use app\models\Places;
use app\models\SchedulesAclSearch;
use app\models\Services;
use Yii;
use app\models\Schedules;
use yii\web\NotFoundHttpException;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class ScheduledAccessController extends ArmsBaseController
{
	public $modelClass=Schedules::class;
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['status']
		]);
	}
	
	
	/**
	 * Lists all Schedules models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		//Services::cacheAllItems();
		//Places::cacheAllItems();
		$searchModel = new SchedulesAclSearch();
		$model= new $this->modelClass();
		$columns=DynaGridWidget::fetchVisibleAttributes($model,StringHelper::class2Id($this->modelClass).'-index');
		$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount??null,
		]);
	}
	
	/**
	 * Displays a single model status
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionStatus(int $id)
	{
		/** @var Schedules $model */
		$model=$this->findModel($id);
		return $model->isWorkTime(
			gmdate('Y-m-d',time()+Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+Yii::$app->params['schedulesTZShift'])
		);
	}
	
	
	/**
	 * Displays a single Schedules model.
	 * @param int  $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    public function actionView(int $id)
    {
    	/** @var Schedules $model */
    	$model=$this->findModel($id);
    	if ($model->isOverride) {
    		$params=Yii::$app->request->get();
    		$params['id']=$model->override_id;
    		return $this->redirect(array_merge(['view'],$params));
		}
    		
        return $this->render('view', [
            'model' => $model,
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
			$acl=new Acls();
			$acl->schedules_id=$model->id;
			$acl->save();
			return $this->redirect(['view', 'id' => $model->id, 'acl_mode'=>1] );
		}
		
		return $this->render('create', [
			'model' => $model,
		]);
	}
	
}
