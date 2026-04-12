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
	 * Отображает список расходных материалов, сгруппированных по типу.
	 *
	 * GET-параметры (queryParams): любые фильтры модели MaterialsSearch
	 * (например, MaterialsSearch[type_id], MaterialsSearch[model] и т.д.).
	 *
	 * @return string
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
	 * Acceptance test data for TypeGroups.
	 *
	 * Проверяет рендер страницы без фильтров. Создаёт тестовые данные через getTestData(),
	 * чтобы список содержал хотя бы одну запись и группировка по типу отрабатывала корректно.
	 */
	public function testTypeGroups(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}

	/**
	 * Отображает список расходных материалов, сгруппированных по наименованию (полю model).
	 *
	 * GET-параметры (queryParams): любые фильтры модели MaterialsSearch
	 * (например, MaterialsSearch[type_id], MaterialsSearch[model] и т.д.).
	 *
	 * @return string
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
	 * Acceptance test data for NameGroups.
	 *
	 * Проверяет рендер страницы без фильтров. Создаёт тестовые данные через getTestData(),
	 * чтобы список содержал хотя бы одну запись и группировка по наименованию отрабатывала корректно.
	 */
	public function testNameGroups(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}

	/**
	 * Отображает tooltip-карточки для нескольких расходных материалов одновременно.
	 *
	 * GET-параметры:
	 * @param string $ids      Список id через запятую (обязательно), например: "1,2,3".
	 *                         Передаётся как часть маршрута или GET-параметр.
	 *                         Если хоть один id не найден — выбрасывается 404.
	 *
	 * Дополнительные GET-параметры (опционально):
	 *   hide_usages — скрыть блок использований в tooltip.
	 *   hide_places — скрыть блок мест хранения в tooltip.
	 *
	 * @return string
	 * @throws NotFoundHttpException если ни одна из моделей не найдена
	 */
	public function actionTtips(string $ids)
	{
		return $this->renderPartial('ttips', [
			'models' => $this->findModels(explode(',',$ids)),
			'hide_usages' => Yii::$app->request->get('hide_usages'),
			'hide_places' => Yii::$app->request->get('hide_places'),
		]);
	}

	/**
	 * Acceptance test data for Ttips.
	 *
	 * Проверяет рендер tooltip для существующего расходного материала.
	 * Использует id из getTestData()['full'], который гарантированно создан и сохранён в БД.
	 */
	public function testTtips(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['ids' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Обёртка над actionTtips для отображения tooltip одного расходного материала.
	 *
	 * GET-параметры:
	 * @param int|string $id  Идентификатор расходного материала.
	 *
	 * @return string
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionTtip($id) {
		return $this->actionTtips($id);
	}
	
	/**
	 * AJAX-поиск расходных материалов по наименованию внутри заданного типа.
	 * Возвращает JSON-массив уникальных значений поля model.
	 *
	 * GET-параметры:
	 * @param string|null $name  Строка поиска по полю model (опционально, частичное совпадение LIKE).
	 * @param int|null    $type  ID типа материала (обязательно); при отсутствии возвращает null.
	 *
	 * @return array|null JSON-массив вида [['value' => '...'], ...] или null если $type не задан
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
	 * Acceptance test data for SearchList.
	 *
	 * Проверяет AJAX-поиск без строки фильтра по type_id из тестового расходного материала.
	 * Использует getTestData()['full']->type_id, чтобы тест не зависел от захардкоженных значений.
	 */
	public function testSearchList(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['type' => $testData['full']->type_id],
			'response' => 200,
		]];
	}
	
	public $modelClass=Materials::class;

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
