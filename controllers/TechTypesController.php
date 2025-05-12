<?php

namespace app\controllers;

use Yii;
use app\models\TechTypes;
use yii\web\NotFoundHttpException;

/**
 * TechTypesController implements the CRUD actions for TechTypes model.
 */
class TechTypesController extends ArmsBaseController
{
	public $modelClass=TechTypes::class;

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-template'],
		]);
	}
	
	public function disabledActions()
	{
		return ['ttip'];
	}
	
    /**
     * Displays a single TechTypes model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
	    $params=Yii::$app->request->queryParams;
	    
	    $model=$this->findModel($id);
			
		if (!isset($params['TechsSearch'])) $params['TechsSearch']=[];
		$params['TechsSearch']['type_id']=$id;
		$searchModel = new \app\models\TechsSearch();
		$dataProvider = $searchModel->search($params);
		return $this->render('view', [
			'model' => $model,
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
			
    }


	/**
	 * Displays a single OrgPhones model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionHintTemplate(int $id)
	{
		$model=$this->findModel($id);
		return Yii::$app->formatter->asNtext($model->comment);
	}
}
