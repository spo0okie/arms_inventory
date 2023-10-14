<?php

namespace app\controllers;

use app\models\Techs;
use Yii;
use app\models\Ports;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * PortsController implements the CRUD actions for Ports model.
 */
class PortsController extends ArmsBaseController
{

	public $modelClass=Ports::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['port-list',],
		]);
	}
	
	

	public function actionCreate() {
		return $this->actionUpdate(null);
	}
	
    /**
     * Updates an existing Ports model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int|null $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(int $id=null)
    {
        $model = is_null($id)?
			$model=new Ports():
			$this->findModel($id);
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->defaultReturn(['view', 'id' => $model->id],[$model]);
		}
	
		$model->load(Yii::$app->request->get());
		return $this->defaultRender('update', ['model' => $model,]);
    }

	
	/**
	 * Returns tech available network ports
	 * @return array
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_all_params'])) {
			$params = $_POST['depdrop_all_params'];
			if (is_array($params)) {
				if (isset($params['link_techs_id']) && strlen($params['link_techs_id'])) {
					$model=Techs::findOne($params['link_techs_id']);
					return ['output'=>$model->ddPortsList, 'selected'=>''];
				} else {
					return ['output'=>[], 'selected'=>''];
				}
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	
	/**
	 * Finds the Ports model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @param null    $failRoute
	 * @return Ports the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    protected function findModel(int $id, $failRoute=null)
    {
        if (($model = Ports::findOne($id)) !== null) {
            return $model;
        }
		
        if (!is_null($failRoute)) {
			$this->redirect($failRoute);
		}
        
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
