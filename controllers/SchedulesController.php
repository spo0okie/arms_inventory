<?php

namespace app\controllers;

use app\helpers\StringHelper;
use app\models\MaintenanceJobs;
use app\models\SchedulesEntries;
use app\models\Services;
use Throwable;
use Yii;
use app\models\Schedules;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * SchedulesController implements the CRUD actions for Schedules model.
 */
class SchedulesController extends ArmsBaseController
{
	public $modelClass='app\models\Schedules';
	
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
		
		$support_service=null;
		$service=null;
		$job=null;
		$item=null;

		//
		if (Yii::$app->request->get('attach_service')) {
			$service= Services::findOne(Yii::$app->request->get('attach_service'));
			if (is_object($service)) {
				$model->name= Schedules::$title.' работы '.StringHelper::mb_lcfirst($service->name);
			}
		} elseif (Yii::$app->request->get('support_service')) {
			$support_service= Services::findOne(Yii::$app->request->get('support_service'));
			if (is_object($service)) {
				$model->name= Schedules::$title.' поддержки '.StringHelper::mb_lcfirst($support_service->name);
			}
		} elseif (Yii::$app->request->get('attach_job')) {
			$job= MaintenanceJobs::findOne(Yii::$app->request->get('attach_job'));
			if (is_object($job)) {
				$model->name= Schedules::$title.' '.StringHelper::mb_lcfirst($job->name);
			}
		}

		$model->load(Yii::$app->request->get());
		
		if ($model->override_id) {
			//$model->parent_id = $model->override_id;
			$model->start_date = date('Y-m-d');
			$model->name='Override for #'.$model->override_id;
		}

		if ($model->load(Yii::$app->request->post())) {
			if ($model->save()) {
				//если было указано расписание по умолчанию - надо его создать в БД
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
				} elseif (is_object($job)) { //или поддержку сервиса
					$job->schedules_id = $model->id;
					$job->save();
					return $this->defaultReturn(['maintenance-jobs/view', 'id' => $job->id],[$model]);
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
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
		/** @var Schedules $model */
    	$model=$this->findModel($id);
    	$parent=$model->parent_id;
        $this->findModel($id)->delete();

        return $this->redirect($parent?['view', 'id' => $parent]:['index']);
    }
}
