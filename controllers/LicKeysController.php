<?php

namespace app\controllers;


use Yii;
use app\models\LicKeys;
use app\models\LicKeysSearch;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * LicKeysController implements the CRUD actions for LicKeys model.
 */
class LicKeysController extends Controller
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
			    ['allow' => true, 'actions'=>['index','view','ttip'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
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
     * Lists all LicKeys models.
     * @return mixed
     */
    public function actionIndex()
    {
	    $searchModel = new LicKeysSearch();
	    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
	        'searchModel' => $searchModel,
	        'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single LicKeys model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'linksData'=>new ArrayDataProvider([
				'allModels' => \app\models\links\LicLinks::findForLic('keys',$id),
				'key'=>'id',
				'sort' => [
					'attributes'=> [
						'objName',
						'comment',
						'changedAt',
						'changedBy',
					],
					'defaultOrder' => [
						'objName' => SORT_ASC
					]
				],
				'pagination' => false,
			]),
		]);
    }

    /**
     * Creates a new LicKeys model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new LicKeys();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return [$model];
			}  else {
				return $this->redirect(Url::previous());
			}
        }
	
		if (Yii::$app->request->get('lic_items_id'))
			$model->lic_items_id=Yii::$app->request->get('lic_items_id');
		
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
     * Updates an existing LicKeys model.
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
				return $this->redirect(Url::previous());
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
     * Deletes an existing LicKeys model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
    	$model=$this->findModel($id);
    	$lic_items_id=$model->lic_items_id;
        $model->delete();

	    return $this->redirect(['/lic-items/view', 'id' => $lic_items_id]);
    }

    /**
     * Finds the LicKeys model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LicKeys the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LicKeys::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
