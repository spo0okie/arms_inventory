<?php

namespace app\controllers;

use Yii;
use app\models\Aces;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;


/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends Controller
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
     * Lists all Aces models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Aces::find(),
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
			$model = new Aces();

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
     * Displays a single Aces model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id,$ajax=0)
    {
        return $ajax?$this->renderAjax('card', [
			'model' => $this->findModel($id),
		]):
			$this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Aces model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($acls_id, $modal=null)
    {
        $model = new Aces();
        $model->acls_id = $acls_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return ['success' => true,];
			}
	
			//if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['acls/view', 'id' => $model->acls_id]);
        }

        return Yii::$app->request->isAjax?
			$this->renderAjax('_form', [
				'model' => $model,
				'modalParent'=>'#'.$modal
			]):
			$this->render('create', [
            	'model' => $model,
        	]);
    }

    /**
     * Updates an existing Aces model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id,$ajax=0,$modal=null)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return [
					'success' => true,
				];
			}

			//if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['acl/view', 'id' => $model->acls_id]);
        }

        return $ajax?
			$this->renderAjax('_form', [
				'model' => $model,
				'modalParent'=>'#'.($modal?$modal:'modal_form_loader'),
			]):
			$this->render('update', [
				'model' => $model,
			]);
    }

    /**
     * Deletes an existing Aces model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
    	$ace=$this->findModel($id);
    	$acl=$ace->acl;
    	
        $ace->delete();
	
		if (Yii::$app->request->get('return')=='previous')
			return $this->redirect(Url::previous());
		
		if (is_object($acl) && $acl->schedules_id)
			return $this->redirect(['/scheduled-access/view','id'=>$acl->schedules_id]);
		
		
		$this->redirect(['/scheduled-access/index']);
    }

    /**
     * Finds the Aces model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Aces the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Aces::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
