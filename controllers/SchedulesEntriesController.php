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
class SchedulesEntriesController extends Controller
{
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
     * Lists all SchedulesDays models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SchedulesEntriesSearch();
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
            return $this->redirect(['/schedules/view', 'id' => $model->schedule_id]);
        }
	
		if (isset($_GET['schedule_id'])) $model->schedule_id=$_GET['schedule_id'];
		if (isset($_GET['is_period'])) $model->is_period=$_GET['is_period'];
        if (isset($_GET['date'])) $model->date=$_GET['date'];

        return $this->render('create', [
            'model' => $model,
        ]);
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
            return $this->redirect(['/schedules/view', 'id' => $model->schedule_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
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
        $schedule_id=$this->findModel($id)->schedule_id;
        $this->findModel($id)->delete();

        return $this->redirect(['/schedules/view', 'id' => $schedule_id]);
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
