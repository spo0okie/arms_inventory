<?php

namespace app\controllers;

use app\models\TechModels;
use Yii;
use app\models\Scans;
use app\models\ScansSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap\ActiveForm;

/**
 * ScansController implements the CRUD actions for Scans model.
 */
class ScansController extends Controller
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
			    ['allow' => true, 'actions'=>['create','update','delete','unlink','thumb'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }

    /**
     * Lists all Scans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ScansSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Scans model.
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
	 * Validates Manufacturers model on update.
	 * @param null $id
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionValidate($id=null)
	{
		if (!is_null($id))
			$model = $this->findModel($id);
		else
			$model = new Scans();

		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}

    /**
     * Creates a new Scans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Scans();

	    if ($model->load(Yii::$app->request->post())) {
		    $model->scanFile = UploadedFile::getInstance($model, 'scanFile');
		    if (!$model->validate()) {
		    	$errors=[];
			    foreach ($model->getErrors() as $attribute => $errors) {
				    $errors[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
			    }
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return (object)[
			    	'error'=>'не прошло валидацию',
				    'validation'=>$errors
			    ];

		    }
		    if (!$model->upload()) return "{\"error\":\"не удалось загрузить\"}";
		    if ($model->save(false)) {
			    // тут у нас уже произошло успешное создание объекта и он уже в базе
				// это очевидно какаято устаревшая дичь со времен many-2-many отношений с доками
			    /* if ($contracts_id=Yii::$app->request->get('contracts_id')) {
			    	//а тут мы обнаружили, что надо этот скан прикрутить к конрактам
				    if (is_object($contract=\app\models\Contracts::findOne(['id'=>$contracts_id]))) {
				    	$contract_scans=$contract->scans_ids;
					    $contract_scans[]=$model->id;
				    	$contract->scans_ids=$contract_scans;
				    	$contract->save();
				    }
			    } */
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return [$model];
		    }
		    return '{"error":"ошибка сохранения модели"}';
	    }
	    return '{"error":"ошибка получения данных"}';

    }
	
	/**
	 * Updates an existing Scans model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			if ($model->upload()&&$model->save())
				return $this->redirect(['view', 'id' => $model->id]);
		}
		
		return $this->render('update', [
			'model' => $model,
		]);
	}
	
	/**
	 * Updates an existing Scans model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @param string  $link
	 * @param integer $link_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionThumb($id,$link,$link_id)
	{
		switch ($link) {
			case 'tech_models_id':
				$model = \app\models\TechModels::findOne($link_id);
				break;
			case 'techs_id':
				$model = \app\models\Techs::findOne($link_id);
				break;
			default:
				$model=null;
		}
		if ($model === null)
			throw new NotFoundHttpException('The requested page does not exist.');
		
		$model->scans_id=$id;
		$model->save(false);
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return (object)['code'=>'0'];
	}
	
	
	/**
     * Deletes an existing Scans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @param integer $contracts_id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id=null)
    {
    	if (is_null($id)) $id=Yii::$app->request->post('key');
    	$model=$this->findModel($id);
        $model->delete();

        if (file_exists($_SERVER['DOCUMENT_ROOT'].$model->fullFname))
            unlink($_SERVER['DOCUMENT_ROOT'].$model->fullFname);

	    if (Yii::$app->request->isAjax) {
		    Yii::$app->response->format = Response::FORMAT_JSON;
		    return (object)['code'=>'0'];
	    }
	    return $this->redirect(['index']);
    }

    /**
     * Finds the Scans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Scans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Scans::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
