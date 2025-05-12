<?php

namespace app\controllers;

use app\models\SchedulesEntries;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * SchedulesDaysController implements the CRUD actions for SchedulesDays model.
 */
class SchedulesEntriesController extends ArmsBaseController
{
	public $modelClass=SchedulesEntries::class;
	
	public function disabledActions()
	{
		return ['item-by-name','view'];
	}
	
	/**
	 * Displays a single model ttip.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip(int $id)
	{
		if ($t=Yii::$app->request->get('timestamp')) {
			return $this->renderPartial('ttip', [
				'model' => $this->findJournalRecord($id,$t),
				'positive' => Yii::$app->request->getQueryParam( 'positive',[]),
				'negative' => Yii::$app->request->getQueryParam('negative',[]),
			]);
		}
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'positive' => Yii::$app->request->getQueryParam( 'positive',[]),
			'negative' => Yii::$app->request->getQueryParam('negative',[]),
		]);
	}
	

    /**
     * Creates a new SchedulesDays model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SchedulesEntries();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return (is_object($model->master) && $model->master->isAcl)?
				$this->defaultReturn(['/scheduled-access/view', 'id' => $model->schedule_id],[$model]):
				$this->defaultReturn(['/schedules/view', 'id' => $model->schedule_id],[$model]);
        }
	
		$model->load(Yii::$app->request->get());
	
		return $this->defaultRender('create', ['model' => $model,]);
    }

    /**
     * Updates an existing SchedulesDays model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id)
	{
		/** @var SchedulesEntries $model */
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return (is_object($model->master) && $model->master->isAcl)?
				$this->defaultReturn(['/scheduled-access/view', 'id' => $model->schedule_id],[$model]):
				$this->defaultReturn(['/schedules/view', 'id' => $model->schedule_id],[$model]);
		}
		
		$model->load(Yii::$app->request->get());
		
		return $this->defaultRender('update', ['model' => $model,]);
	}
	
	/**
	 * Deletes an existing SchedulesDays model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var SchedulesEntries $item */
    	$item=$this->findModel($id);
    	//запоминаем мастера чтобы потом понятно было с чем мы работали с простым расписанием или расписанием доступа
    	$schedule=$item->master;
    	
        $item->delete();
	
		return (is_object($schedule) && $schedule->isAcl)?
			$this->redirect(['/scheduled-access/view', 'id' => $schedule->id]):
			$this->redirect(['/schedules/view', 'id' => $schedule->id]);
    }


}
