<?php

namespace app\controllers;

use Yii;
use app\models\Partners;
use app\models\PartnersSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PartnersController implements the CRUD actions for Partners model.
 */
class PartnersController extends Controller
{
    /**
     * @inheritdoc
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
			    ['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }

    /**
     * Lists all Partners models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PartnersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Partners model.
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
     * Creates a new Partners model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Partners();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Partners model.
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
     * Deletes an existing Partners model.
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
     * Finds the Partners model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Partners the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Partners::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
