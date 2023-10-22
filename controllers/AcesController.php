<?php

namespace app\controllers;

use Throwable;
use Yii;
use app\models\Aces;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;


/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends ArmsBaseController
{
	public $modelClass=Aces::class;
	
	/**
	 * Deletes an existing Aces model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var Aces $ace */
    	$ace=$this->findModel($id);
    	$acl=$ace->acl;
    	
        $ace->delete();
	
		if (Yii::$app->request->get('return')=='previous')
			return $this->redirect(Url::previous());
		
		if (is_object($acl) && $acl->schedules_id)
			return $this->redirect(['/scheduled-access/view','id'=>$acl->schedules_id]);
		
		return $this->redirect(['/scheduled-access/index']);
    }
}
