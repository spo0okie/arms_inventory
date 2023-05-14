<?php

namespace app\controllers;

use app\models\Attaches;
use app\models\TechModels;
use Yii;
use app\models\Scans;
use app\models\ScansSearch;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap5\ActiveForm;

/**
 * AttachesController implements the CRUD actions for Scans model.
 */
class AttachesController extends ArmsBaseController
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
			    ['allow' => true, 'actions'=>['create','delete'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['ttip'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }
    

    /**
     * Creates a new Scans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Attaches();

	    if ($model->load(Yii::$app->request->get())) {
		    $model->uploadedFile = UploadedFile::getInstance($model, 'uploadedFile');
		    if ($model->upload()) $model->save();
	    }
		return $this->redirect(Url::previous());

    }
    
	
	
	/**
     * Deletes an existing Scans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id=null)
    {
    	//if (is_null($id)) $id=Yii::$app->request->post('id');
    	$model=$this->findModel($id);
        $model->delete();

        if (file_exists($_SERVER['DOCUMENT_ROOT'].$model->fullFname))
            unlink($_SERVER['DOCUMENT_ROOT'].$model->fullFname);
	
		return $this->redirect(Url::previous());
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
        if (($model = Attaches::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
