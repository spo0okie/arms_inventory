<?php

namespace app\controllers;

use Yii;
use app\models\ManufacturersDict;
use app\models\ManufacturersDictSearch;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ManufacturersDictController implements the CRUD actions for ManufacturersDict model.
 */
class ManufacturersDictController extends Controller
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
     * Lists all ManufacturersDict models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ManufacturersDictSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ManufacturersDict model.
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
     * Creates a new ManufacturersDict model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ManufacturersDict();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['manufacturers/view', 'id' => $model->manufacturers_id]);
        }

        $model->manufacturers_id=Yii::$app->request->get('manufacturers_id');
        $model->word=Yii::$app->request->get('word');

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing ManufacturersDict model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['manufacturers/view', 'id' => $model->manufacturers_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing ManufacturersDict model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $manufacturers_id=$this->findModel($id)->manufacturers_id;
        $this->findModel($id)->delete();

        return $this->redirect(['manufacturers/view', 'id' => $manufacturers_id]);
    }

    /**
     * Finds the ManufacturersDict model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ManufacturersDict the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ManufacturersDict::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
