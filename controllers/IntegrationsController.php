<?php

namespace app\controllers;

use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\PanelsCache;
use app\helpers\DocsHelper;
use app\models\base\ArmsModel;
use Yii;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Proxy-контроллер механизма интеграций
 * (docs/dev/integrations.md) — единственная точка входа для
 * панелей чтения (L1) и действий (L2/L2+) всех провайдеров.
 *
 * Доступ: на уровне маршрутизации оба действия открыты (PERM_EVERYONE),
 * т.к. права адресные и зависят от параметров запроса
 * (view-integration-<provider>, edit-integration-<provider>-<action>) —
 * проверка выполняется внутри действий через
 * IntegrationsRegistry::userCanView()/userCanRun(), которые следуют модели
 * авторизации ядра (useRBAC/authorizedView, docs/help/admin/setup.md).
 */
class IntegrationsController extends ArmsBaseController
{
	/**
	 * Контроллер не модельный: стандартный CRUD отключаем точечно.
	 * @return array<string>
	 */
	public function disabledActions(): array
	{
		return ['index', 'async-grid', 'item', 'item-by-name', 'ttip', 'view', 'validate', 'create', 'update', 'delete', 'editable'];
	}

	public function accessMap()
	{
		//адресная проверка прав - внутри действий (см. класс-комментарий)
		return [ArmsBaseController::PERM_EVERYONE => ['panel', 'action']];
	}

	/**
	 * Рендер панели чтения (L1): ajax-запрос из PanelsWidget.
	 *
	 * GET:
	 *   provider (string) — id провайдера;
	 *   panel (string)    — id панели;
	 *   class (string)    — kebab-case класс объекта (users, techs, ...);
	 *   id (int)          — id объекта.
	 *
	 * Отдаёт готовый HTML панели: свежий кэш как есть, иначе рендер
	 * провайдера с сохранением в кэш. Ошибка рендера не перетирает кэш
	 * и возвращает компактную заглушку (контракт §3.1).
	 *
	 * @return string HTML панели
	 * @throws NotFoundHttpException
	 */
	public function actionPanel(string $provider, string $panel, string $class, $id)
	{
		$providerObj = $this->findProvider($provider);
		$model = $this->findIntegrationModel($providerObj, $class, $id);
		if (!IntegrationsRegistry::userCanView($providerObj)) $this->denyAccess();
		if (!isset($providerObj->panels($model)[$panel]))
			throw new NotFoundHttpException("Panel '$panel' not found");

		$binding = $providerObj->binding($model);

		//пока ajax летел, кэш мог успеть стать свежим - не рендерим зря
		if (!is_null($binding)
			&& ($cached = PanelsCache::fetch($providerObj->id, $panel, $binding))
			&& $cached['age'] <= $providerObj->panelTtl($panel, $model)
		) return $cached['html'];

		try {
			$html = $providerObj->renderPanel($panel, $model);
		} catch (\Throwable $e) {
			Yii::warning("Integration panel {$providerObj->id}/$panel failed: ".$e->getMessage(), __METHOD__);
			//в debug-режиме показываем причину прямо в панели (помощь при
			//настройке); на проде — нейтральная заглушка, детали в логе
			$detail = YII_DEBUG
				? ': '.Html::encode($e->getMessage())
				: ': недоступно';
			return '<span class="text-secondary opacity-75">'
				.Html::encode($providerObj->getTitle()).$detail.'</span>';
		}
		if (!is_null($binding)) PanelsCache::store($providerObj->id, $panel, $binding, $html);
		return $html;
	}

	/**
	 * Форма и выполнение действия (L2/L2+), открывается в модалке.
	 *
	 * GET (рендер формы):
	 *   provider (string) — id провайдера;
	 *   action (string)   — id действия;
	 *   class, id         — объект (опционально: standalone-действия без них);
	 *   <FormName>[...]   — предзаполнение формы (prefill).
	 * POST (выполнение):
	 *   поля формы действия;
	 *   ext_login, ext_password — личные креды внешней ИС (только L2+).
	 *
	 * @return string HTML формы или результата
	 * @throws NotFoundHttpException
	 */
	public function actionAction(string $provider, string $action, string $class = null, $id = null)
	{
		$providerObj = $this->findProvider($provider);
		$model = ($class !== null && $id !== null) ?
			$this->findIntegrationModel($providerObj, $class, $id) : null;

		$descriptor = $providerObj->actions($model)[$action] ?? null;
		if (!$descriptor) throw new NotFoundHttpException("Action '$action' not found");
		if (is_null($model) && empty($descriptor['standalone']))
			throw new NotFoundHttpException("Action '$action' requires an object");

		if (!IntegrationsRegistry::userCanRun($providerObj, $action)) $this->denyAccess();

		$isPersonal = ($descriptor['level'] ?? IntegrationProvider::LEVEL_NORMAL) === IntegrationProvider::LEVEL_PERSONAL;
		$credentialsError = null;

		$form = IntegrationsRegistry::buildActionForm($descriptor);
		if ($form->load(Yii::$app->request->post()) && $form->validate()) {
			$credentials = $this->collectCredentials();
			if ($isPersonal && is_null($credentials)) {
				//L2+ без кредов не выполняем - возвращаем форму с ошибкой
				$credentialsError = 'Укажите учетные данные, от имени которых выполнить действие';
			} else {
				$result = IntegrationsRegistry::runActionForm($providerObj, $action, $model, $form,
					$isPersonal ? $credentials : null);
				return $this->defaultRender('action-response', [
					'provider' => $providerObj,
					'descriptor' => $descriptor,
					'result' => $result,
				]);
			}
		}

		$form->load(Yii::$app->request->get());
		return $this->defaultRender('action-form', [
			'provider' => $providerObj,
			'actionId' => $action,
			'descriptor' => $descriptor,
			'form' => $form,
			'model' => $model,
			'isPersonal' => $isPersonal,
			'credentialsError' => $credentialsError,
		]);
	}

	/** Провайдер по id либо 404 */
	protected function findProvider(string $id): IntegrationProvider
	{
		$provider = IntegrationsRegistry::provider($id);
		if (!$provider) throw new NotFoundHttpException("Integration '$id' is not enabled");
		return $provider;
	}

	/**
	 * Объект ARMS по kebab-case классу и id, с проверкой применимости
	 * провайдера. Класс резолвится только в наследников ArmsModel
	 * ({@see DocsHelper::findDocClass()}) — произвольные классы из GET
	 * не инстанцируются.
	 * @throws NotFoundHttpException
	 */
	protected function findIntegrationModel(IntegrationProvider $provider, string $classId, $id): ArmsModel
	{
		$class = DocsHelper::findDocClass($classId);
		if (!$class) throw new NotFoundHttpException("Class '$classId' not found");
		/** @var ArmsModel $model */
		$model = $class::findOne($id);
		if (!is_object($model)) throw new NotFoundHttpException("Object $classId:$id not found");
		if (!$provider->appliesTo($model))
			throw new NotFoundHttpException("Integration '{$provider->id}' is not applicable to $classId:$id");
		return $model;
	}

	/**
	 * Личные креды внешней ИС из POST (L2+): используются на один вызов,
	 * нигде не сохраняются; пароль не журналируется
	 * @return array|null ['login'=>,'password'=>] либо null если не заполнены
	 */
	protected function collectCredentials(): ?array
	{
		$login = trim((string)Yii::$app->request->post('ext_login', ''));
		$password = (string)Yii::$app->request->post('ext_password', '');
		if ($login === '' || $password === '') return null;
		return ['login' => $login, 'password' => $password];
	}

	/** Отказ в доступе в семантике базового контроллера */
	protected function denyAccess(): void
	{
		if (Yii::$app->user->isGuest)
			throw new UnauthorizedHttpException('Unauthorized access');
		throw new ForbiddenHttpException('Access denied');
	}

	/**
	 * Acceptance test data for Panel.
	 *
	 * Внешние ИС в тестовой среде недоступны, поэтому проверяются только
	 * маршрутизация и коды отказов: неизвестный провайдер и панель,
	 * которой нет у настроенного провайдера (sms из params-test.php).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function testPanel(): array
	{
		return [
			[
				'name' => 'unknown-provider',
				'GET' => ['provider' => 'no-such-provider', 'panel' => 'x', 'class' => 'users', 'id' => 1],
				'response' => 404,
			],
			[
				'name' => 'no-such-panel',
				'GET' => ['provider' => 'sms', 'panel' => 'no-such-panel', 'class' => 'users', 'id' => 1],
				'response' => 404,
			],
		];
	}

	/**
	 * Acceptance test data for Action.
	 *
	 * Тестовая среда — полностью открытый режим (useRBAC=false,
	 * authorizedView=false): действия доступны всем, включая гостя, как и
	 * обычные правки (docs/help/admin/setup.md). Поэтому форма и POST
	 * отдают 200; неизвестный провайдер — 404. Проверяются маршрутизация и
	 * доступ; сама отправка (data://-шлюз) — unit-тестами SmsProvider.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function testAction(): array
	{
		return [
			[
				'name' => 'unknown-provider',
				'GET' => ['provider' => 'no-such-provider', 'action' => 'send'],
				'response' => 404,
			],
			[
				'name' => 'open-mode-form',
				'GET' => ['provider' => 'sms', 'action' => 'send'],
				'response' => 200,
			],
			[
				'name' => 'open-mode-post',
				'GET' => ['provider' => 'sms', 'action' => 'send'],
				'POST' => [
					'SmsSendForm' => [
						'phone' => '79991234567',
						'text' => 'Acceptance message',
					],
				],
				'response' => 200,
			],
		];
	}
}
