<?php

namespace app\controllers;

use Yii;
use app\models\Materials;
use app\models\MaterialsSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * MaterialsController implements the CRUD actions for Materials model.
 */
class MaterialsController extends ArmsBaseController
{
	public $modelClass=Materials::class;
	
	public function disabledActions()
	{
		return ['item-by-name'];
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['name-groups','type-groups','ttips','search-list'],
		]);
	}
	
	/**
	 * Lists all Materials models by groups.
	 * @return mixed
	 */
	public function actionTypeGroups()
	{
		$searchModel = new MaterialsSearch();
		$dataProvider = $searchModel->searchTypeGroups(Yii::$app->request->queryParams);
		
		return $this->render('groups', [
			'groupBy'=>'type',
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	
	/**
	 * Lists all Materials models by groups.
	 * @return mixed
	 */
	public function actionNameGroups()
	{
		$searchModel = new MaterialsSearch();
		$dataProvider = $searchModel->searchNameGroups(Yii::$app->request->queryParams);
		
		return $this->render('groups', [
			'groupBy'=>'name',
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Displays a many models ttip.
	 * @param string $ids
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtips(string $ids)
	{
		return $this->renderPartial('ttips', [
			'models' => $this->findModels(explode(',',$ids)),
			'hide_usages' => Yii::$app->request->get('hide_usages'),
			'hide_places' => Yii::$app->request->get('hide_places'),
		]);
	}
	
	public function actionTtip($id) {
		return $this->actionTtips($id);
	}
	
	/**
	 * @param string|null $name
	 * @param int|null $type
	 * @return mixed
	 */
    public function actionSearchList($name = null,$type = null) {
    	if (empty($type)) return null;
		$materials=\app\models\Materials::find()
			->select('model')
			->distinct()
			->andFilterWhere(['type_id'=>$type])
			->andFilterWhere(['like','model',$name])
			->all();
		$out = [];
		foreach ($materials as $d) {
			/** @var Materials $d */
			$out[] = ['value' => $d->model];
		}
	    Yii::$app->response->format = Response::FORMAT_JSON;
		return $out;
	}


	/**
	 * Finds the Materials models based on its primary key values.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param array $ids
	 * @return Materials[] the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModels(array $ids)
	{
		if (count($models = Materials::findAll($ids))) {
			return $models;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
}
