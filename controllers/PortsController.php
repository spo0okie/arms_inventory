<?php

namespace app\controllers;

use app\models\Techs;
use Yii;
use app\models\Ports;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * PortsController implements the CRUD actions for Ports model.
 *
 * Управляет сетевыми портами оборудования (Ports).
 * Порт всегда привязан к Techs (link_techs_id) и может ссылаться
 * на сетевое соединение. Предоставляет AJAX-бэкенд для Dependent Dropdown
 * списка портов по выбранному оборудованию.
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

	/**
	 * Создаёт новый порт, делегируя выполнение в actionUpdate(null).
	 *
	 * POST-параметры (через Ports::load):
	 * - Ports[link_techs_id] (int, обязательно): ID оборудования, к которому привязан порт
	 * - прочие поля модели Ports
	 *
	 * @return mixed
	 */
	public function actionCreate() {
		return $this->actionUpdate(null);
	}

	/**
	 * Acceptance test data for actionCreate.
	 *
	 * Тест создаёт новый порт через стандартный flow ArmsBaseController::testCreate().
	 * Требует, чтобы ModelFactory корректно генерировал Ports с валидным link_techs_id.
	 *
	 * @return array
	 */
	public function testCreate(): array
	{
		return parent::testCreate();
	}

    /**
     * Создаёт новый порт (id = null) или редактирует существующий (id != null).
     *
     * GET-параметры:
     * - id (int|null, опционально): ID порта; null означает создание нового
     *
     * POST-параметры (через Ports::load):
     * - Ports[link_techs_id] (int, обязательно): ID оборудования
     * - прочие поля модели Ports
     *
     * @param int|null $id GET: ID порта (null = создание)
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
	 * Acceptance test data for actionUpdate.
	 *
	 * Тест обновляет существующий порт через стандартный сценарий ArmsBaseController::testUpdate().
	 * Тестовая запись Ports создаётся через getTestData()['full'] перед запросом.
	 *
	 * @return array
	 */
	public function testUpdate(): array
	{
		return parent::testUpdate();
	}

	/**
	 * AJAX-бэкенд Dependent Dropdown: возвращает список портов для выбранного оборудования.
	 *
	 * Используется при выборе оборудования в форме, чтобы заполнить поле выбора порта.
	 * Вызывает Techs::ddPortsList для получения списка в формате DepDrop.
	 *
	 * POST-параметры:
	 * - depdrop_all_params[link_techs_id] (int, обязательно): ID оборудования (Techs)
	 *
	 * Ответ в формате JSON:
	 * - при наличии данных: ['output' => [...], 'selected' => '']
	 * - при отсутствии link_techs_id: ['output' => [], 'selected' => '']
	 * - при ошибке: ['output' => '', 'selected' => '']
	 *
	 * @return array JSON-ответ для DepDrop
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
	 * Acceptance test data for actionPortList.
	 *
	 * Что делает actionPortList:
	 * - принимает DepDrop POST payload `depdrop_all_params[link_techs_id]`;
	 * - ищет оборудование (Techs) по переданному ID;
	 * - возвращает JSON вида `['output' => ..., 'selected' => '']`.
	 *
	 * Что проверяет этот тест:
	 * 1) endpoint не падает на корректном depdrop payload и существующем ID оборудования;
	 * 2) endpoint корректно обрабатывает depdrop payload без `link_techs_id`;
	 * 3) endpoint корректно обрабатывает запрос без depdrop payload.
	 *
	 * Почему этого достаточно для acceptance-контракта:
	 * - задача теста на этом уровне — подтвердить доступность UI-action и стабильный
	 *   HTTP-ответ на ожидаемые формы запроса;
	 * - бизнес-содержимое списка портов (`output`) зависит от данных в дампе,
	 *   поэтому здесь проверяется именно устойчивость action и формат входа.
	 *
	 * @return array
	 */
	public function testPortList(): array
	{
		$testData = $this->getTestData();
		$techId = (int)($testData['full']->link_techs_id ?? 0);
		if ($techId <= 0) {
			$techId = (int)Techs::find()->select('id')->scalar();
		}
		if ($techId <= 0) {
			return self::skipScenario('default', 'no Techs records available in acceptance db dump');
		}

		return [
			[
				'name' => 'depdrop with valid tech id',
				'POST' => ['depdrop_all_params' => ['link_techs_id' => $techId]],
				'response' => 200,
			],
			[
				'name' => 'depdrop without tech id',
				'POST' => ['depdrop_all_params' => []],
				'response' => 200,
			],
			[
				'name' => 'request without depdrop payload',
				'POST' => [],
				'response' => 200,
			],
		];
	}

	/**
	 * Acceptance test data for actionItem (унаследованного).
	 *
	 * Тест пропущен: карточка порта (item) требует, чтобы порт был привязан
	 * к существующему оборудованию (Techs) и/или сетевому соединению (Networks).
	 * Без этих связей шаблон item может выбрасывать исключения при рендере.
	 * Необходимо расширить ModelFactory для Ports с гарантированным link_techs_id.
	 *
	 * @return array
	 */
	public function testItem(): array
	{
		$testData = $this->getTestData();
		return [
			['name' => 'item full',  'GET' => ['id' => $testData['full']->id],  'response' => 200],
			['name' => 'item empty', 'GET' => ['id' => $testData['empty']->id], 'response' => 200],
		];
	}

	/**
	 * Acceptance test data for actionTtip (унаследованного).
	 *
	 * Тест пропущен: всплывающая подсказка порта рендерится с данными
	 * из связанного Techs и Networks. Без валидных связей тест нестабилен.
	 * Необходимо расширить ModelFactory для Ports с гарантированным link_techs_id.
	 *
	 * @return array
	 */
	public function testTtip(): array
	{
		$testData = $this->getTestData();
		return [
			['name' => 'ttip full',  'GET' => ['id' => $testData['full']->id],  'response' => 200],
			['name' => 'ttip empty', 'GET' => ['id' => $testData['empty']->id], 'response' => 200],
		];
	}

	/**
	 * Acceptance test data for actionView (унаследованного).
	 *
	 * Тест пропущен: страница просмотра порта требует валидных связей на Techs/Networks.
	 * Порт сам по себе (без привязанного оборудования) не имеет осмысленного
	 * контекста для отображения, что делает тест ненадёжным.
	 * Необходимо расширить ModelFactory для Ports с гарантированным link_techs_id.
	 *
	 * @return array
	 */
	public function testView(): array
	{
		$testData = $this->getTestData();
		return [
			['name' => 'view full',  'GET' => ['id' => $testData['full']->id],  'response' => 200],
			['name' => 'view empty', 'GET' => ['id' => $testData['empty']->id], 'response' => 200],
		];
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
