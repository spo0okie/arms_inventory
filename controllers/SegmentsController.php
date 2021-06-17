<?php

namespace app\controllers;

use app\models\NetworksSearch;
use Yii;
use app\models\Segments;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;


/**
 * SegmentsController implements the CRUD actions for Segments model.
 */
class SegmentsController extends Controller
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
				['allow' => true, 'actions'=>['create','update','delete',], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
	}

    /**
     * Lists all Segments models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Segments::find(),
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				'defaultOrder' => ['name'=>SORT_ASC],
			]
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
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
			$model = new Segments();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}


	/**
	* Displays a item for single model.
	* @param integer $id
	* @return mixed
	* @throws NotFoundHttpException if the model cannot be found
	*/
	public function actionItem($id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
		]);
	}


	/**
	* Displays a tooltip for single model.
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
     * Displays a single Segments model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
		$searchModel = new NetworksSearch();
		$searchModel->segments_id=$id;
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
	
		return $this->render('view', [
            'model' => $this->findModel($id),
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Segments model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Segments();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Segments model.
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
     * Deletes an existing Segments model.
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
     * Finds the Segments model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Segments the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Segments::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
