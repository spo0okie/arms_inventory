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
	 * Страница управления загруженными файлами для типа расходного материала.
	 *
	 * Рендерит представление uploads для указанного типа материала.
	 * Страница открывается и без прикреплённых файлов — их отсутствие не вызывает ошибку.
	 *
	 * GET-параметры:
	 * @param int $id  Идентификатор типа расходного материала (обязательно).
	 *
	 * @return string
	 * @throws NotFoundHttpException если тип материала не найден
	 */
	public function actionUploads(int $id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
			'model' => $model,
		]);
	}
	
		
	/**
	 * Acceptance test data for Uploads.
	 *
	 * Страница uploads рендерится без ошибок даже при отсутствии прикреплённых файлов.
	 * Тип материала создаётся через getTestData()['full'], поэтому skip не нужен —
	 * для открытия страницы достаточно наличия записи MaterialsTypes в БД.
	 */
	public function testUploads(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}

	/**
	 * Страница просмотра типа расходного материала со списком материалов данного типа.
	 *
	 * Поддерживает два режима отображения списка материалов:
	 *   - без groupBy (или любое значение кроме 'name'): плоский список через MaterialsSearch::search()
	 *   - groupBy='name': список, сгруппированный по наименованию, через MaterialsSearch::searchNameGroups()
	 *
	 * GET-параметры:
	 * @param int         $id       Идентификатор типа расходного материала (обязательно).
	 * @param string|null $groupBy  Режим группировки; поддерживается значение 'name' (опционально).
	 *
	 * @return string
	 * @throws NotFoundHttpException если тип материала не найден
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
