<?php

namespace app\controllers;

use Yii;
use app\models\Techs;
use app\models\TechsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap\ActiveForm;
use yii\web\Response;
use yii\helpers\Url;

/**
 * TechsController implements the CRUD actions for Techs model.
 */
class TechsController extends Controller
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
			    ['allow' => true, 'actions'=>['index','view','ttip','validate','inv-num'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }

	/**
	 * Displays a single OrgPhones model.
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
	 * Формирует префикс и возвращает следующий инвентарный номер в этом префиксе
	 * @param null $model_id
	 * @param null $place_id
	 * @param null $arm_id
	 * @return mixed
	 */
	public function actionInvNum($model_id=null,$place_id=null,$arm_id=null)
	{
		$prefix=\app\models\Techs::genInvPrefix($model_id,$place_id,$arm_id);
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return \app\models\Techs::fetchNextNum($prefix);
	}

    /**
     * Lists all Techs models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TechsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Techs model.
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
     * Creates a new Techs model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Techs();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
	        if (Yii::$app->request->isAjax) {
		        Yii::$app->response->format = Response::FORMAT_JSON;
		        return [$model];
	        }  else {
		        return $this->redirect(['view', 'id' => $model->id]);
	        }
        }

        $model->arms_id=Yii::$app->request->get('arms_id');

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Techs model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
	        if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
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
			$model = new \app\models\Techs();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}

	/**
	 * Deletes an existing Techs model.
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

        return $this->redirect(['index']);
    }

    /**
     * Finds the Techs model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Techs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Techs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
