<?php

namespace app\controllers;

use app\models\OrgPhones;
use Yii;
use app\models\OrgInet;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * OrgInetController implements the CRUD actions for OrgInet model.
 */
class OrgInetController extends Controller
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
			    ['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
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
     * Lists all OrgInet models.
     * @return mixed
     */
    public function actionIndex()
    {
		$query=OrgInet::find();
		if (!Yii::$app->request->get('showArchived',false))
			$query->where(['not',['archived'=>1]]);
	
		$dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
        ]);
	
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single OrgInet model.
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
     * Creates a new OrgInet model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OrgInet();
	
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return [$model];
			}  else {
				return $this->redirect(Url::previous());
			}
		}
	
		$model->load(Yii::$app->request->get());
		$model->prov_tel_id=Yii::$app->request->get('prov-tel');
	
		return Yii::$app->request->isAjax?
			$this->renderAjax('create', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('create', [
				'model' => $model,
			]);

    }

    /**
     * Updates an existing OrgInet model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return [$model];
			}  else {
				if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}
	
	
		return Yii::$app->request->isAjax?
			$this->renderAjax('update', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('update', [
				'model' => $model,
			]);
    }

    /**
     * Deletes an existing OrgInet model.
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
     * Finds the OrgInet model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OrgInet the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OrgInet::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
