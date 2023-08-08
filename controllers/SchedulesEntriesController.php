<?php

namespace app\controllers;

use app\models\SchedulesEntries;
use Yii;
use app\models\SchedulesDays;
use app\models\SchedulesDaysSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SchedulesDaysController implements the CRUD actions for SchedulesDays model.
 */
class SchedulesEntriesController extends ArmsBaseController
{
	public $modelClass='app\models\SchedulesEntries';
	
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
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
			'positive' => Yii::$app->request->getQueryParam( 'positive',[]),
			'negative' => Yii::$app->request->getQueryParam('negative',[]),
		]);
	}
	
	/**
     * Displays a single SchedulesDays model.
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
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
	{
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
     */
    public function actionDelete($id)
    {
    	
    	$item=$this->findModel($id);
    	//запоминаем мастера чтобы потом понятно было с чем мы работали с простым расписанием или расписанием доступа
    	$schedule=$item->master;
    	
        $item->delete();
	
		return (is_object($schedule) && $schedule->isAcl)?
			$this->redirect(['/scheduled-access/view', 'id' => $schedule->id]):
			$this->redirect(['/schedules/view', 'id' => $schedule->id]);
    }

    /**
     * Finds the SchedulesDays model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SchedulesEntries the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SchedulesEntries::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
