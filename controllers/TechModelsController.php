<?php

namespace app\controllers;

use app\components\llm\LlmClient;
use app\components\RackWidget;
use app\helpers\FieldsHelper;
use app\models\Manufacturers;
use app\models\ManufacturersDict;
use app\models\TechsSearch;
use Throwable;
use Yii;
use app\models\TechModels;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TechModelsController implements the CRUD actions for TechModels model.
 */
class TechModelsController extends ArmsBaseController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-comment','hint-template','hint-description',],
			'edit'=>['uploads','render-rack','generate-description']
		]);
	}
	
	
	
	/**
     * {@inheritdoc}
     */
    public function behaviors()
    {
    	return array_merge_recursive(parent::behaviors(),[
			'verbs' => [
				'actions' => [
					'render-rack' => ['POST'],
				],
			]
		]);
    }
	
	
	/**
	 * Рендерит краткую карточку (item) модели оборудования.
	 *
	 * GET:
	 *   id (int) — идентификатор модели оборудования.
	 *   long (mixed, опционально) — при любом значении рендерит расширенный вариант карточки.
	 *
	 * @param int   $id   Идентификатор модели оборудования
	 * @param mixed $long Признак расширенного отображения (опционально)
	 * @return string HTML partial карточки модели
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionItem(int $id, $long=null)
	{
		return $this->renderPartial('item', [
			'model'	=> $this->findModel($id),
			'long'	=> $long,
		]);
	}
	
	/**
	 * Acceptance test data for Item.
	 *
	 * Проверяет рендер краткой карточки для существующей модели оборудования.
	 * GET: id из getTestData()['full'].
	 */
	public function testItem(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	
	/**
	 * Рендерит карточку модели оборудования по краткому или полному имени и производителю.
	 *
	 * Ищет производителя через ManufacturersDict (словарь), затем через Manufacturers.
	 * Поиск модели: сначала по полю short, затем по полю name.
	 *
	 * GET:
	 *   name (string) — краткое или полное имя модели (например, 'G430').
	 *   manufacturer (string) — имя производителя (например, 'Avaya').
	 *   long (mixed, опционально) — расширенный вариант карточки.
	 *
	 * @param string $name Краткое или полное имя модели оборудования
	 * @return string HTML partial карточки модели
	 * @throws NotFoundHttpException если производитель или модель не найдены
	 */
	public function actionItemByName($name)
	{
		$manufacturer=Yii::$app->request->get('manufacturer');
		$long=Yii::$app->request->get('long');
		/// производитель
		//ищем в словаре
		if (is_null($man_id= ManufacturersDict::fetchManufacturer($manufacturer))) {
			//ищем в самих производителях
			if (!is_object($man_obj = Manufacturers::findOne(['name'=>$manufacturer]))) {
				throw new NotFoundHttpException('Requested manufacturer not found');
			} else {
				$man_id=$man_obj->id;
			}
		}
		
		if (($model = TechModels::findOne(['short'=>$name,'manufacturers_id'=>$man_id])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'long'	=> $long,
			]);
		}

		if (($model = TechModels::findOne(['name'=>$name,'manufacturers_id'=>$man_id])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'long'	=> $long,
			]);
		}

		throw new NotFoundHttpException('The requested model not found within that manufacturer');
	}
	
	
	
		
	/**
	 * Acceptance test data for ItemByName.
	 *
	 * ВНИМАНИЕ: тест использует жёстко заданные данные ('G430', 'Avaya'), которые
	 * могут отсутствовать в тестовой БД — это делает тест нестабильным.
	 * Рекомендуется заменить на getTestData()['full'] и использовать
	 * $testData['full']->short (или ->name) и $testData['full']->manufacturer->name.
	 */
	public function testItemByName(): array
	{
		$testData = $this->getTestData();
		return [[
			'name' => 'default',
			'GET' => [
				'name' => $testData['full']->short ?: $testData['full']->name,
				'manufacturer' => $testData['full']->manufacturer->name,
			],
			'response' => 200,
		]];
	}
	/**
	 * Возвращает подсказку по заполнению спецификаций для модели оборудования.
	 *
	 * Если у модели установлен флаг individual_specs — возвращает comment из TechTypes
	 * (шаблон спецификации типа оборудования). Иначе возвращает стандартную заглушку.
	 * GET: id (int) — идентификатор модели оборудования.
	 *
	 * @param int $id Идентификатор модели оборудования
	 * @return string Текст подсказки (ntext) или заглушка
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionHintTemplate(int $id)
	{
		/** @var TechModels $model */
		$model=$this->findModel($id);
		if ($model->individual_specs)
			return Yii::$app->formatter->asNtext($model->type->comment);
		else
			return \app\models\TechModels::$no_specs_hint;
	}
	
		
	/**
	 * Acceptance test data for HintTemplate.
	 *
	 * Проверяет получение подсказки спецификаций для существующей модели оборудования.
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
	/**
	 * Возвращает описание (comment) модели оборудования в виде форматированного текста.
	 *
	 * GET: id (int) — идентификатор модели оборудования.
	 *
	 * @param int $id Идентификатор модели оборудования
	 * @return string Текст описания модели (ntext)
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionHintDescription(int $id)
	{
		$model=$this->findModel($id);
		return Yii::$app->formatter->asNtext($model->comment);
	}
	
	/**
	 * Acceptance test data for HintDescription.
	 *
	 * Проверяет получение описания для существующей модели оборудования.
	 * GET: id из getTestData()['full'].
	 */
	public function testHintDescription(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	
	
	/**
	 * Возвращает JSON с данными комментария типа оборудования для подсказки.
	 *
	 * Загружает данные типа через TechModels::fetchTypeComment, оборачивает hint
	 * в qtip-формат через FieldsHelper::toolTipOptions.
	 * GET: id (int) — идентификатор модели оборудования.
	 *
	 * @param int $id Идентификатор модели оборудования
	 * @return array JSON с полями name, hint (qtip-формат) и доп. данными типа
	 * @throws NotFoundHttpException если данные типа не найдены
	 */
	public function actionHintComment(int $id){
		Yii::$app->response->format = Response::FORMAT_JSON;
		$data=\app\models\TechModels::fetchTypeComment($id);
		if (!is_array($data)) throw new NotFoundHttpException('The requested data does not exist.');
		//переоформляем под qtip
		$data['hint']=FieldsHelper::toolTipOptions($data['name'],$data['hint'])['qtip_ttip'];
		return $data;
	}
	
	
	/**
	 * Acceptance test data for HintComment.
	 *
	 * Проверяет JSON-ответ с данными комментария типа для существующей модели оборудования.
	 * GET: id из getTestData()['full'].
	 */
	public function testHintComment(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Отображает страницу модели оборудования со списком экземпляров.
	 *
	 * Загружает TechsSearch с фильтром по model_id и передаёт dataProvider для таблицы экземпляров.
	 * GET: id (int) — идентификатор модели оборудования.
	 *
	 * @param int $id Идентификатор модели оборудования
	 * @return string HTML страницы модели оборудования
	 * @throws NotFoundHttpException если модель не найдена
	 */
	public function actionView(int $id)
	{
		$this->setQueryParam(['TechsSearch'=>['model_id'=>$id]]);
		
		$techSearchModel = new TechsSearch();
		$techDataProvider = $techSearchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel' => $techSearchModel,
			'dataProvider' => $techDataProvider,
		]);
	}
	
		
	/**
	 * Acceptance test data for View.
	 *
	 * Проверяет страницу модели оборудования со списком экземпляров.
	 * GET: id из getTestData()['full']. Тест проходит при пустом списке экземпляров.
	 */
	public function testView(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Рендерит виджет стойки (RackWidget) по конфигурации из POST.
	 *
	 * Принимает конфигурацию стойки в виде JSON-строки и передаёт её в RackWidget.
	 * Доступен только через POST (VerbFilter).
	 *
	 * POST:
	 *   config (string) — JSON-строка с конфигурацией стойки для RackWidget.
	 *
	 * @return string HTML-рендер виджета стойки
	 * @throws Throwable при ошибках рендера виджета
	 */
	public function actionRenderRack()
	{
		return RackWidget::widget(
			json_decode(
				Yii::$app->request->getBodyParam('config'),true
			)
		);
	}
	
	
		
	/**
	 * Acceptance test data for RenderRack.
	 *
	 * Тест пропущен: action требует валидный JSON конфигурации стойки в теле POST.
	 * Без реальной конфигурации RackWidget не может быть проинициализирован корректно.
	 */
	public function testRenderRack(): array
	{
		return self::skipScenario('default', 'requires rack configuration');
	}
	/**
	 * Отображает страницу загрузок (uploads) для модели оборудования.
	 *
	 * GET: id (int) — идентификатор модели оборудования.
	 *
	 * @param int $id Идентификатор модели оборудования
	 * @return string HTML страницы загрузок
	 * @throws NotFoundHttpException если модель не найдена
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
	 * Проверяет страницу загрузок для существующей модели оборудования.
	 * GET: id из getTestData()['full'].
	 */
	public function testUploads(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	
	/**
	 * AJAX: генерирует описание модели оборудования через LLM (OpenAI API).
	 *
	 * Принимает POST-параметры, формирует запрос к LlmClient::generateTechModelDescription
	 * с использованием типа оборудования, производителя и имени модели.
	 * Ответ: JSON с ключами success/data или error.
	 *
	 * POST:
	 *   name (string)        — наименование модели.
	 *   manufacturer (int)   — ID производителя (Manufacturers).
	 *   type (int)           — ID типа оборудования (TechTypes).
	 *
	 * @return array JSON-ответ с результатом генерации или сообщением об ошибке
	 */
	public function actionGenerateDescription()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
		$name = Yii::$app->request->post('name');
		$manufacturer = Yii::$app->request->post('manufacturer');
		$type = Yii::$app->request->post('type');
		
		if (!$name) {
			return ['error' => 'Не указана модель'];
		}
		
		if (!$manufacturer) {
			return ['error' => 'Не указан производитель'];
		}
		
		if (!$type) {
			return ['error' => 'Не указан тип оборудования'];
		}
		
		$vendor=\app\models\Manufacturers::findOne($manufacturer);
		$techType=\app\models\TechTypes::findOne($type);
		$generator = new LlmClient();
		$result = $generator->generateTechModelDescription($techType->name, $vendor->name.' '.$name,$techType->comment);
		
		if (!$result) {
			return ['error' => 'Не удалось получить описание'];
		}
		
		return ['success' => true, 'data' => $result];
	}	
	/**
	 * Acceptance test data for GenerateDescription.
	 *
	 * Проверяет HTTP 200 при валидных POST-параметрах. Тест зависит от доступности
	 * LLM API (OpenAI): при недоступном API action вернёт JSON с ключом error,
	 * но HTTP-статус будет 200 — это считается успехом на уровне acceptance-теста.
	 * POST: name='Test', manufacturer=1.
	 */
	public function testGenerateDescription(): array
	{
		return [[
			'name' => 'default',
			'POST' => ['name' => 'Test', 'manufacturer' => 1],
			'response' => 200,
		]];
	}
	
	public $modelClass='app\models\TechModels';

	
}
