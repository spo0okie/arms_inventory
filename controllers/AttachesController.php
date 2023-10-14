<?php

namespace app\controllers;

use app\models\Attaches;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * AttachesController implements the CRUD actions for Scans model.
 */
class AttachesController extends ArmsBaseController
{

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
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete(int $id)
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
     * @param int $id
     * @return Attaches the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id)
    {
        if (($model = Attaches::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
