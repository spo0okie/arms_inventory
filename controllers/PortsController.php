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
			'edit'=>['port-list','scan-apply',],
		]);
	}

	/**
	 * Применить находку опроса коммутатора к порту (plans/network-map.md, 3.4).
	 *
	 * Опрос показывает расхождение между записанным и увиденным, а это
	 * действие переносит увиденное в инвентаризацию: привязать обнаруженное
	 * оборудование, заменить им записанное или снять связь.
	 *
	 * Связь всегда идёт порт-в-порт: `peer` задаёт порт на той стороне (id
	 * существующего либо имя для создания), а без него оборудование
	 * привязывается «целиком» - к безымянному порту, который заведёт
	 * {@see Ports::beforeSave()}. Встречную связь и отвязку прежнего соседа
	 * модель делает сама.
	 *
	 * Кроме связей, опрос приносит два факта о самом коммутаторе: как он собрал
	 * порты в группы и как она их называет. Оба переносятся тем же действием -
	 * это ровно то же «увиденное вместо записанного», только без второй стороны.
	 *
	 * POST:
	 * - tech (int)    - id коммутатора, чей порт правим (коммутатор);
	 * - port (string) - имя порта;
	 * - do (string)   - attach (привязать/заменить), chain (привязать
	 *                   устройство и за ним ещё одно: телефон и ПК за ним),
	 *                   detach (снять связь), aggregate (пометить группу) либо
	 *                   names (взять имена портов с коммутатора);
	 * - device (int)      - id обнаруженного оборудования (для attach/chain);
	 * - peer (int)        - id существующего порта на той стороне;
	 * - peer_name (string)- имя порта, который надо там создать;
	 * - via / via_name    - порт устройства, из которого идёт кабель дальше
	 *                       (для chain; PC-порт телефона);
	 * - leaf (int)        - id устройства за ним (ПК);
	 * - leaf_peer / leaf_peer_name - порт на этом устройстве;
	 * - aggregate (string)- имя группы (для aggregate);
	 * - members (string)  - имена портов группы через перевод строки;
	 * - names (string)    - имена портов коммутатора по порядку, через перевод строки.
	 *
	 * id и имя - разные параметры намеренно: порт вполне может называться «5»,
	 * и по одному значению «5» было бы не разобрать, это id или имя.
	 *
	 * @return array JSON-ответ {status: ok|error, error?: string}
	 */
	public function actionScanApply()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		$request = Yii::$app->request;

		$switch = Techs::findOne((int)$request->post('tech'));
		if (!is_object($switch)) return ['status' => 'error', 'error' => 'не найдено оборудование'];

		//действия над коммутатором целиком - до разбора имени порта: порта у них нет
		if ($request->post('do') === 'aggregate') return $this->applyAggregate($switch, $request);
		if ($request->post('do') === 'names') return $this->applyNames($switch, $request);

		$name = trim((string)$request->post('port'));
		if (!strlen($name)) return ['status' => 'error', 'error' => 'не указан порт'];

		$port = Ports::forTech($switch, $name);

		if ($request->post('do') === 'detach') {
			$port->dropLink();
			return ['status' => 'ok'];
		}

		$device = Techs::findOne((int)$request->post('device'));
		if (!is_object($device))
			return ['status' => 'error', 'error' => 'не указано оборудование'];

		$peerId = (int)$request->post('peer');
		$peerName = trim((string)$request->post('peer_name'));

		$port->link_techs_id = $device->id;
		//есть id - берём готовый порт, есть имя - создаём, нет ничего -
		//привязываем к устройству целиком (безымянный порт заведёт модель)
		$port->link_ports_id = $peerId ?: (strlen($peerName) ? 'create:'.$peerName : null);

		if (!$port->save() && !$port->isNewRecord && count($port->errors)) {
			return ['status' => 'error', 'error' => implode('; ', $port->firstErrors)];
		}
		if ($request->post('do') !== 'chain') return ['status' => 'ok'];

		//второе звено: из порта моста (PC-порт телефона) - в лист (ПК). Это
		//такая же связь порт-в-порт, только обе стороны не коммутатор
		$leaf = Techs::findOne((int)$request->post('leaf'));
		if (!is_object($leaf)) return ['status' => 'error', 'error' => 'не указано устройство за мостом'];

		$viaId = (int)$request->post('via');
		$viaName = trim((string)$request->post('via_name'));
		$via = $viaId ? Ports::findOne($viaId) : (strlen($viaName) ? Ports::forTech($device, $viaName) : null);
		if (!is_object($via) || (int)$via->techs_id !== (int)$device->id)
			return ['status' => 'error', 'error' => 'не указан порт моста'];

		$leafPeerId = (int)$request->post('leaf_peer');
		$leafPeerName = trim((string)$request->post('leaf_peer_name'));
		$via->link_techs_id = $leaf->id;
		$via->link_ports_id = $leafPeerId ?: (strlen($leafPeerName) ? 'create:'.$leafPeerName : null);
		if (!$via->save() && !$via->isNewRecord && count($via->errors)) {
			return ['status' => 'error', 'error' => implode('; ', $via->firstErrors)];
		}
		return ['status' => 'ok'];
	}

	/**
	 * Пометить порты ярлыком группы так, как её собрал коммутатор.
	 *
	 * Группа - ярлык на нескольких портах, а не порт: связи у членов свои,
	 * меняется только поле `aggr`. Помечаем всех членов сразу - по одному
	 * ярлык смысла не имеет.
	 *
	 * @return array JSON-ответ
	 */
	protected function applyAggregate(Techs $switch, $request): array
	{
		$aggregate = trim((string)$request->post('aggregate'));
		$members = array_filter(array_map('trim',
			preg_split('~[\r\n]+~', (string)$request->post('members'))));

		if (!strlen($aggregate) || !count($members))
			return ['status' => 'error', 'error' => 'не указан агрегат'];

		foreach ($members as $member) {
			$port = Ports::forTech($switch, $member);
			$port->aggr = $aggregate;
			if (!$port->save() && count($port->errors))
				return ['status' => 'error', 'error' => implode('; ', $port->firstErrors)];
		}
		return ['status' => 'ok'];
	}

	/**
	 * Назвать порты так, как их называет сам коммутатор.
	 *
	 * Имена портов - свойство экземпляра, а не модели оборудования: стек
	 * перенумеровывает порты, MikroTik позволяет переименовать интерфейсы, и
	 * модельные Ge0/1..24 после этого фантомы. Заведённые строки переезжают
	 * за своими позициями ({@see Techs::renamePortsByPosition()}): за именем
	 * стоит кабель, и он никуда не делся.
	 *
	 * @return array JSON-ответ
	 */
	protected function applyNames(Techs $switch, $request): array
	{
		$names = array_filter(array_map('trim',
			preg_split('~[\r\n]+~', (string)$request->post('names'))));
		if (!count($names)) return ['status' => 'error', 'error' => 'не переданы имена портов'];

		$switch->ports_override = implode("\n", $names);
		//смысл кнопки - те же розетки под именами коммутатора, так что заведённые
		//порты переезжают за позициями без вопросов (в форме это выбор)
		$switch->rename_ports = true;
		//без валидации: имена портов к остальной карточке отношения не имеют,
		//а спотыкаться о её застарелые огрехи это действие не должно
		if (!$switch->save(false)) return ['status' => 'error', 'error' => 'не удалось сохранить'];

		return ['status' => 'ok'];
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
