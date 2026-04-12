<?php

namespace app\controllers;

use Yii;
use app\models\TechTypes;
use yii\web\NotFoundHttpException;

/**
 * TechTypesController implements the CRUD actions for TechTypes model.
 *
 * Управляет типами оборудования: просмотр типа с перечнем экземпляров,
 * подсказки по шаблонам спецификаций. Action 'ttip' отключён.
 */
class TechTypesController extends ArmsBaseController
{
	/**
	 * Acceptance test data for HintComment.
	 *
	 * ВНИМАНИЕ: используется placeholder '{anyId}' вместо реального ID —
	 * тест нестабилен при пустой БД. Рекомендуется заменить на getTestData()['full']->id.
	 */
	public function testHintComment(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	/**
	 * Acceptance test data for HintDescription.
	 *
	 * ВНИМАНИЕ: используется placeholder '{anyId}' вместо реального ID —
	 * тест нестабилен при пустой БД. Рекомендуется заменить на getTestData()['full']->id.
	 */
	public function testHintDescription(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}

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
     * Отображает страницу типа оборудования с перечнем всех экземпляров этого типа.
     *
     * Загружает TechsSearch с фильтром type_id=$id для отображения таблицы экземпляров.
     * GET: id (int) — идентификатор типа оборудования.
     *
     * @param int $id Идентификатор типа оборудования
     * @return string HTML страницы типа оборудования
     * @throws NotFoundHttpException если тип не найден
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
	 * Возвращает шаблон-подсказку по заполнению спецификаций для типа оборудования.
	 *
	 * Отдаёт форматированный текст поля comment из TechTypes — используется как
	 * шаблон при заполнении спецификаций экземпляров данного типа.
	 * GET: id (int) — идентификатор типа оборудования.
	 *
	 * @param int $id Идентификатор типа оборудования
	 * @return string Текст шаблона спецификации (ntext)
	 * @throws NotFoundHttpException если тип не найден
	 */
	public function actionHintTemplate(int $id)
	{
		$model=$this->findModel($id);
		return Yii::$app->formatter->asNtext($model->comment);
	}	
	/**
	 * Acceptance test data for HintTemplate.
	 *
	 * Проверяет получение шаблона спецификации для существующего типа оборудования.
	 * GET: id из getTestData()['full'].
	 */
	public function testHintTemplate(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	public $modelClass=TechTypes::class;

}
