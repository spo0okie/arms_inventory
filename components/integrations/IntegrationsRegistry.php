<?php

namespace app\components\integrations;

use app\controllers\ArmsBaseController;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\IntegrationsLog;
use Yii;
use yii\base\DynamicModel;
use yii\base\Model;

/**
 * Реестр провайдеров интеграций (docs/dev/integrations.md).
 *
 * Строится из params['integrations'] (инстанс включает свои интеграции в
 * params-local.php). Провайдер попадает в реестр только если у него есть
 * корректный класс и isConfigured() === true.
 *
 * Здесь же:
 * - проверки доступа (userCanView/userCanRun) — общая логика для
 *   контроллера и виджетов, с учётом выключенного useRBAC;
 * - серверное выполнение действий runAction()/runActionForm() с
 *   журналированием — единый путь для proxy-контроллера и вложенных
 *   вызовов из других провайдеров (композиция, §2.2 контракта).
 */
class IntegrationsRegistry
{
	/** @var IntegrationProvider[]|null кэш построенного реестра */
	private static ?array $providers = null;

	/**
	 * Все включённые и настроенные провайдеры
	 * @return IntegrationProvider[] индексированы по id
	 */
	public static function providers(): array
	{
		if (static::$providers !== null) return static::$providers;
		static::$providers = [];
		foreach (Yii::$app->params['integrations'] ?? [] as $id => $config) {
			if (!is_array($config)) continue;
			$class = $config['class'] ?? null;
			if (!$class || !class_exists($class) || !is_subclass_of($class, IntegrationProvider::class)) {
				Yii::warning("Integration '$id': invalid provider class '$class'", __METHOD__);
				continue;
			}
			/** @var IntegrationProvider $provider */
			$provider = new $class();
			$provider->id = (string)$id;
			$provider->config = $config;
			if (!$provider->isConfigured()) continue;
			static::$providers[$id] = $provider;
		}
		return static::$providers;
	}

	/** Провайдер по id (null если не включён/не настроен) */
	public static function provider(string $id): ?IntegrationProvider
	{
		return static::providers()[$id] ?? null;
	}

	/** Сброс кэша реестра (для тестов) */
	public static function reset(): void
	{
		static::$providers = null;
	}

	/**
	 * Может ли текущий пользователь видеть панели провайдера.
	 * Панель = «просмотр»: доступ по той же модели, что и view-действия
	 * ядра (params useRBAC/authorizedView, docs/help/admin/setup.md).
	 */
	public static function userCanView(IntegrationProvider $provider): bool
	{
		return static::checkAccess(ArmsBaseController::PERM_VIEW.'-integration-'.$provider->id);
	}

	/**
	 * Может ли текущий пользователь выполнить действие провайдера.
	 * Действие = «изменение»: доступ по той же модели, что и edit-действия
	 * ядра (docs/help/admin/setup.md). Право
	 * edit-integration-<id>-<action> создаётся rbac/init.
	 */
	public static function userCanRun(IntegrationProvider $provider, string $actionId): bool
	{
		return static::checkAccess(ArmsBaseController::PERM_EDIT.'-integration-'.$provider->id.'-'.$actionId);
	}

	/**
	 * Проверка доступа к интеграции ровно по модели авторизации ядра.
	 * Имя права начинается с view-/edit-, чтобы
	 * {@see ArmsBaseController::customizeAccessPermission()} применил к нему
	 * те же правила, что и к обычным операциям (единый источник правды:
	 * при useRBAC=false — открытость/аутентификация по authorizedView; при
	 * useRBAC=true просмотр открыт всем, если authorizedView выключен).
	 *
	 * При useRBAC=true доступ даёт адресное право (view-integration-<id> /
	 * edit-integration-<id>-<action>) ЛИБО глобальный «зонтик» view/edit —
	 * ровно как в {@see ArmsBaseController::buildAccessRules()}, где на
	 * каждое действие заводится и правило под глобальное право, и под
	 * адресное. Поэтому пользователю с глобальным edit доступны все
	 * действия интеграций, с view — все панели (отдельно раздавать
	 * integration-права не требуется).
	 */
	protected static function checkAccess(string $permission): bool
	{
		if (!Yii::$app->has('user')) return false;

		switch (ArmsBaseController::customizeAccessPermission($permission)) {
			case ArmsBaseController::PERM_EVERYONE:
				return true;
			case ArmsBaseController::PERM_AUTHENTICATED:
				return !Yii::$app->user->isGuest;
			case ArmsBaseController::PERM_ANONYMOUS:
				return false;
			default:
				//адресное право ИЛИ глобальный зонтик (view / edit)
				$umbrella = explode('-', $permission, 2)[0];
				return Yii::$app->user->can($permission) || Yii::$app->user->can($umbrella);
		}
	}

	/**
	 * Конфиги колонок интеграций для грида сущности (списочный режим,
	 * §5 «Колонки в списках»): обход реестра (включён + настроен + RBAC + есть колонки для
	 * класса) — как PanelsWidget, но для гридов. Дописывает
	 * {@see \app\components\DynaGridWidget::prepareColumns()}; ключ
	 * колонки — стабильный attribute (dynagrid хранит видимость по нему).
	 *
	 * $modelClass может быть search-наследником (грид строится по
	 * filterModel) — контракт gridColumns() требует от провайдера
	 * проверки через is_a(..., true).
	 *
	 * @return array [attributeKey => конфиг колонки CellColumn]
	 */
	public static function gridColumnConfigs(string $modelClass): array
	{
		$columns = [];
		foreach (static::providers() as $provider) {
			if (!static::userCanView($provider)) continue;
			foreach ($provider->gridColumns($modelClass) as $columnId => $descriptor) {
				$columns['integration-'.$provider->id.'-'.$columnId] = [
					'class' => CellColumn::class,
					'provider' => $provider,
					'columnId' => $columnId,
					'label' => $descriptor['title'] ?? $provider->getTitle(),
					'hint' => $descriptor['hint'] ?? null,
					//живые колонки дороги: по умолчанию скрыты, пользователь
					//включает через персонализацию DynaGrid
					'visible' => false,
				];
			}
		}
		return $columns;
	}

	/**
	 * Серверное выполнение действия по сырым параметрам (композиция §2.2):
	 * строит и валидирует форму, дальше runActionForm(). RBAC здесь НЕ
	 * проверяется — полномочия даёт право на инициирующее действие
	 * (проверку у пользователя делает контроллер).
	 *
	 * @param string $providerId id провайдера-поставщика
	 * @param string $actionId id действия
	 * @param ArmsModel|null $model объект (null для standalone)
	 * @param array $params атрибуты формы действия (без обёртки formName)
	 * @param array|null $credentials креды L2+ ['login'=>,'password'=>]
	 * @param int|null $parentLogId запись журнала действия-инициатора
	 */
	public static function runAction(string $providerId, string $actionId, ?ArmsModel $model,
		array $params = [], ?array $credentials = null, ?int $parentLogId = null): ActionResult
	{
		$provider = static::provider($providerId);
		if (!$provider) return ActionResult::error("Интеграция '$providerId' не включена или не настроена");

		$descriptor = $provider->actions($model)[$actionId] ?? null;
		if (!$descriptor) return ActionResult::error("Действие '$actionId' недоступно у интеграции '$providerId'");

		$form = static::buildActionForm($descriptor);
		$form->load($params, '');
		if (!$form->validate()) {
			$result = ActionResult::error('Параметры не прошли валидацию: '
				.implode('; ', $form->getErrorSummary(true)));
			$result->logId = IntegrationsLog::write($provider->id, $actionId, $model, $result,
				$credentials['login'] ?? null, $parentLogId);
			return $result;
		}

		return static::runActionForm($provider, $actionId, $model, $form, $credentials, $parentLogId);
	}

	/**
	 * Выполнение действия с уже собранной и провалидированной формой
	 * (путь proxy-контроллера). Журналирует итог и при успехе, и при
	 * ошибке; ошибка провайдера (исключение) — тоже штатный итог.
	 *
	 * Запись журнала открывается ДО вызова провайдера: на время выполнения
	 * её id доступен провайдеру в $provider->activeLogId — составные
	 * действия передают его как parentLogId вложенных вызовов (§2.2).
	 */
	public static function runActionForm(IntegrationProvider $provider, string $actionId, ?ArmsModel $model,
		Model $form, ?array $credentials = null, ?int $parentLogId = null): ActionResult
	{
		$logId = IntegrationsLog::open($provider->id, $actionId, $model,
			$credentials['login'] ?? null, $parentLogId);

		$previousLogId = $provider->activeLogId;
		$provider->activeLogId = $logId;
		try {
			$result = $provider->runAction($actionId, $model, $form, $credentials);
		} catch (\Throwable $e) {
			Yii::error("Integration {$provider->id}/$actionId failed: ".$e->getMessage(), __METHOD__);
			$result = ActionResult::error('Ошибка выполнения: '.$e->getMessage());
		} finally {
			$provider->activeLogId = $previousLogId;
		}

		IntegrationsLog::close($logId, $result);
		$result->logId = $logId;
		return $result;
	}

	/**
	 * Модель формы параметров действия по его дескриптору
	 * (пустой класс формы => форма без параметров)
	 */
	public static function buildActionForm(array $descriptor): Model
	{
		$formClass = $descriptor['form'] ?? '';
		if ($formClass && class_exists($formClass)) return new $formClass();
		return new DynamicModel([]);
	}

	/**
	 * Маршрут открытия формы действия (для виджетов): объект и prefill
	 * дескриптора передаются GET-параметрами, prefill — в имени формы
	 * действия (форма подхватит его при рендере load'ом из GET)
	 * @return array маршрут для Url::to()/Html::a()
	 */
	public static function actionUrl(IntegrationProvider $provider, string $actionId,
		array $descriptor, ?ArmsModel $model): array
	{
		$url = ['/integrations/action', 'provider' => $provider->id, 'action' => $actionId];
		if (is_object($model)) {
			$url['class'] = StringHelper::class2Id(get_class($model));
			$url['id'] = $model->id;
		}
		if (!empty($descriptor['prefill']) && !empty($descriptor['form'])) {
			$formName = (new $descriptor['form']())->formName();
			foreach ($descriptor['prefill'] as $attribute => $value) {
				$url[$formName.'['.$attribute.']'] = $value;
			}
		}
		return $url;
	}
}
