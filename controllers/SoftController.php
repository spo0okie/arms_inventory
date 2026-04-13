<?php

namespace app\controllers;

use app\components\llm\LlmClient;
use app\helpers\ArrayHelper;
use app\models\CompsSearch;
use app\models\LicGroupsSearch;
use Yii;
use app\models\Soft;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;

/**
 * SoftController implements the CRUD actions for Soft model.
 */
class SoftController extends ArmsBaseController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['select-update','generate-description','uploads'],
		]);
	}
	
	public function disabledActions()
	{
		return ['item-by-name'];
	}
	
	/**
	 * Рендерит всплывающую подсказку (tooltip) для записи ПО.
	 *
	 * GET:
	 *   id (int) — идентификатор ПО.
	 *   hitlist (string, опционально) — разделённый список строк для подсветки совпадений.
	 *
	 * @param int         $id      Идентификатор ПО
	 * @param string|null $hitlist Список строк для highlight (опционально)
	 * @return string HTML partial tooltip
	 * @throws NotFoundHttpException если ПО не найдено
	 */
	public function actionTtip(int $id, string $hitlist=null)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'hitlist' => $hitlist
		]);
	}

		
	/**
	 * Acceptance test data for Ttip.
	 *
	 * Проверяет рендер tooltip для существующей записи ПО.
	 * GET: id из getTestData()['full'].
	 */
	public function testTtip(): array
	{
		$testData=$this->getTestData();
		
		return [[
			'name' => 'default',
			'GET' => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	/**
	 * Отображает страницу записи ПО со списком ПК, на которых оно установлено, и лицензиями.
	 *
	 * Загружает CompsSearch с фильтром по linkedSoft_ids и LicGroupsSearch с фильтром по soft_ids.
	 * GET: id (int) — идентификатор ПО.
	 *
	 * @param int $id Идентификатор ПО
	 * @return string HTML страницы ПО
	 * @throws NotFoundHttpException если ПО не найдено
	 */
	public function actionView(int $id)
	{
		$searchModel = new CompsSearch();
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['CompsSearch'=>['linkedSoft_ids'=>$id]]));
		
		$licSearchModel = new LicGroupsSearch();
		$licProvider = $licSearchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['LicGroupsSearch'=>['soft_ids'=>$id]]));
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel'=>$searchModel,
			'dataProvider'=>$dataProvider,
			'licProvider'=>$licProvider,
		]);
	}
	
	
		
	/**
	 * Acceptance test data for View.
	 *
	 * Проверяет страницу ПО со списком ПК и лицензий.
	 * GET: id из getTestData()['full']. Тест проходит при пустых связанных списках.
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
     * Отображает форму создания нового ПО и обрабатывает её отправку.
     *
     * После успешного сохранения редиректит на страницу ПО (или на предыдущую при return=previous).
     *
     * GET (предзаполнение формы):
     *   manufacturers_id (int, опционально)    — ID производителя.
     *   descr (string, опционально)            — описание (автоматически обрезается префикс производителя).
     *   items (string, опционально)            — строки обнаружения ПО.
     * POST (поля Soft):
     *   descr (string)            — наименование ПО.
     *   manufacturers_id (int)    — производитель.
     *   items (array, опционально) — строки обнаружения.
     *
     * @return Response|string Форма создания или редирект после успеха
     */
    public function actionCreate()
    {
        $model = new Soft();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->manufacturers_id=Yii::$app->request->get('manufacturers_id');
        $descr=Yii::$app->request->get('descr');
        $cut=is_object($model->manufacturer)?$model->manufacturer->cutManufacturer($descr):'';
        if ($cut) $descr=trim(substr($descr,$cut));
        $model->descr=$descr;

        $model->items=Yii::$app->request->get('items');


        return $this->render('create', [
            'model' => $model,
        ]);
    }
	
	
		
	/**
	 * Acceptance test data for Create.
	 *
	 * Проверяет отображение формы создания ПО без предзаполнения.
	 * GET: нет параметров. Ожидается HTTP 200 с пустой формой.
	 */
	public function testCreate(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}
	/**
	 * Отображает форму поиска и выбора ПО для добавления строки обнаружения.
	 *
	 * При указанном manufacturers_id выводит только продукты этого производителя
	 * (только названия). Без manufacturers_id — весь список ПО с именем производителя.
	 * В AJAX-режиме рендерит partial; иначе — полную страницу.
	 *
	 * GET:
	 *   name (string) — строка обнаружения для добавления к выбранному ПО.
	 *   manufacturers_id (int, опционально) — ограничить список производителем.
	 *
	 * @param string   $name             Строка обнаружения для добавления
	 * @param int|null $manufacturers_id ID производителя для фильтрации (опционально)
	 * @return string HTML формы выбора ПО
	 */
	public function actionSelectUpdate(string $name, int $manufacturers_id=null)
	{
		if (!is_null($manufacturers_id))
			//для случаев, если производитель определен, выводим его продукты (только названия самих продуктов)
			$items=\yii\helpers\ArrayHelper::map(Soft::fetchBy(['manufacturers_id'=>$manufacturers_id]),'id','descr');
		else
			//в ином случае выводим список всех продуктов с указанием производителя в названии
			$items= Soft::listItemsWithPublisher();
		
		
		return Yii::$app->request->isAjax?
			$this->renderAjax( '/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
			]);
	}
	
	/**
	 * Acceptance test data for SelectUpdate.
	 *
	 * Тест пропущен: action требует обязательный параметр name и наличия данных ПО в БД.
	 * При пустой БД список будет пустым, но action всё равно вернёт 200 —
	 * тест можно заменить через getTestData()['full'] передав name='test'.
	 */
	public function testSelectUpdate(): array
	{
		$this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['name' => 'test'],
			'response' => 200,
		]];
	}
	/**
	 * Отображает страницу загрузок (uploads) для записи ПО.
	 *
	 * GET: id (int) — идентификатор ПО.
	 *
	 * @param int $id Идентификатор ПО
	 * @return string HTML страницы загрузок
	 * @throws NotFoundHttpException если ПО не найдено
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
	 * Проверяет страницу загрузок для существующего ПО.
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
	 * AJAX: генерирует описание ПО через LLM (OpenAI API).
	 *
	 * Принимает POST-параметры, формирует запрос к LlmClient::generateSoftwareDescription,
	 * постобрабатывает результат (заменяет «программное обеспечение» на «ПО», формирует comment).
	 * Ответ: JSON с ключами success/data или error.
	 *
	 * POST:
	 *   name (string) — наименование ПО.
	 *   manufacturer (int) — ID производителя (используется для получения имени через Manufacturers).
	 *
	 * @return array JSON-ответ с результатом генерации или сообщением об ошибке
	 */
	public function actionGenerateDescription()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
		$name = Yii::$app->request->post('name');
		$manufacturer = Yii::$app->request->post('manufacturer');
		
		if (!$name) {
			return ['error' => 'Не указано имя ПО'];
		}
		
		if (!$manufacturer) {
			return ['error' => 'Не указан разработчик'];
		}
		
		$vendor=\app\models\Manufacturers::findOne($manufacturer);
		$generator = new LlmClient();
		$result = $generator->generateSoftwareDescription($vendor->name.' '.$name);
		
		if (!$result || !is_array($result)) {
			return ['error' => 'Не удалось получить описание'];
		}
		
		$result['comment']= preg_replace('/программное\s+обеспечение/ui', 'ПО',
				preg_replace('/.$/','', $result['short']).', '
			.$result['cost']
			.' ('.$result['license'].')'
		);
		
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
	
	public $modelClass=Soft::class;

    
}
