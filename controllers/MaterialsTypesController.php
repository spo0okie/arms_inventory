<?php

namespace app\controllers;

use app\models\MaterialsSearch;
use Yii;
use app\models\MaterialsTypes;
use yii\web\NotFoundHttpException;

/**
 * MaterialsTypesController implements the CRUD actions for MaterialsTypes model.
 */
class MaterialsTypesController extends ArmsBaseController
{
	public $modelClass=MaterialsTypes::class;
	
		public function disabledActions()
	{
		return ['item-by-name','ttip'];
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['uploads'],
		]);
	}
	
	
	/**
	 * Updates an existing TechModels model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUploads(int $id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
			'model' => $model,
		]);
	}
	
	/**
	 * Displays a single Arms model.
	 * @param int  $id
	 * @param null $groupBy
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView(int $id, $groupBy=null)
	{
		$searchModel = new MaterialsSearch();
		$searchModel->type_id=$id;
		
		$dataProvider = $groupBy=='name'?
			$searchModel->searchNameGroups(Yii::$app->request->queryParams):
			$searchModel->search(Yii::$app->request->queryParams);
		
		return $this->defaultRender('view', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'model' => $this->findModel($id),
			'groupBy'=>$groupBy,
		]);
	}
}
