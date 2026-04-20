<?php

use app\helpers\StringHelper;
use app\modules\api\controllers\BaseRestController;
use PHPUnit\Framework\Assert;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;

/**
 * Генеративный раннер acceptance-тестов для REST API.
 *
 * Работает аналогично tests/acceptance/PageAccessCest:
 *  - сканирует modules/api/controllers/*.php;
 *  - для каждого action'а контроллера ищет парный testXxx(): array - data-provider сценариев;
 *  - каждый сценарий исполняется как один data-provider элемент test'а testAllEndpointsAccessible().
 *
 * Контракт сценария описан в tests/rest.md.
 */
class RestAccessCest
{
	/**
	 * Реестр сценариев, собранных в routesProvider().
	 * Ключ: controller/action/scenarioName — значение: полный сценарий.
	 *
	 * @var array<string, array>
	 */
	protected static array $scenarioRegistry = [];

	/**
	 * Возвращает объект REST-контроллера по имени файла.
	 *
	 * @param string $file Имя файла контроллера
	 * @return BaseRestController|null
	 */
	protected function getController(string $file): ?BaseRestController
	{
		if (!preg_match('/([A-Za-z0-9]+)Controller\.php$/', $file, $matches)) {
			return null;
		}
		$controllerName = str_replace('Controller', '', $matches[1]);
		$controllerId = StringHelper::class2Id($controllerName);
		$controllerClass = "app\\modules\\api\\controllers\\{$matches[1]}Controller";
		if (!class_exists($controllerClass)) {
			return null;
		}
		$controller = new $controllerClass($controllerId, Yii::$app);
		return $controller instanceof BaseRestController ? $controller : null;
	}

	/**
	 * Возвращает список action'ов контроллера, включая объявленные через actions()
	 * и публичные методы actionXxx().
	 *
	 * @param BaseRestController $controller
	 * @return string[]
	 */
	protected function getActions(BaseRestController $controller): array
	{
		// Не учитываем стандартный Yii 'options' action из actions(): у нас явный actionPreflight.
		$actions = array_diff(array_keys($controller->actions()), ['options']);
		$reflection = new ReflectionClass(get_class($controller));
		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			if (preg_match('/^action([A-Z].+)/', $method->name, $actionMatch)) {
				$actions[] = lcfirst($actionMatch[1]);
			}
		}
		return array_unique($actions);
	}

	/**
	 * Data-provider для testAllEndpointsAccessible.
	 * Вызывается codeception-ом раньше _beforeSuite, поэтому сам поднимает Yii и тестовую БД.
	 *
	 * @return array
	 * @throws InvalidConfigException
	 */
	protected function routesProvider(): array
	{
		$routes = [];
		static::$scenarioRegistry = [];
		// Отключаем debug-модуль на время прогона (см. комментарий в PageAccessCest).
		$config = require __DIR__ . '/../../config/test-web.php';
		$config['bootstrap'] = array_values(array_diff($config['bootstrap'] ?? [], ['debug']));
		unset($config['modules']['debug']);
		if (isset($config['components']['log']['traceLevel'])) {
			$config['components']['log']['traceLevel'] = 0;
		}
		Helper\Yii2::initFromConfig($config);
		codecept_debug('Initializing REST Suite DB...');
		Helper\Database::dropYiiDb();
		Helper\Database::prepareYiiDb();
		Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');

		$routeFilters = $this->parseRouteFilters(getenv('TEST_REST_ROUTES') ?: '');
		$controllersDir = __DIR__ . '/../../modules/api/controllers';
		foreach (scandir($controllersDir) as $file) {
			$controller = $this->getController($file);
			if (!$controller) continue;
			if (get_class($controller) === BaseRestController::class) continue;

			$disabledActions = $controller->disabledActions();
			$disabledTests = $controller->disabledTests();
			if (in_array('*', $disabledTests, true)) continue;

			foreach ($this->getActions($controller) as $action) {
				$actionId = StringHelper::class2Id($action);
				if (in_array($actionId, $disabledActions, true)) continue;
				if (in_array($actionId, $disabledTests, true)) continue;
				if (!$this->isActionSelected($controller->id, $actionId, $routeFilters)) continue;

				$scenarios = $this->getActionScenarios($controller, $actionId);
				foreach ($scenarios as $scenario) {
					$controllerId = StringHelper::class2Id($controller->id);
					$route = $scenario['route'] ?? '{controller}';
					$route = str_replace('{controller}', $controllerId, $route);
					$scenario['route'] = $route;
					$scenario['controllerId'] = $controllerId;
					$scenario['actionId'] = $actionId;
					if (!isset($scenario['name'])) $scenario['name'] = 'default';
					if (!isset($scenario['method'])) $scenario['method'] = 'GET';

					if (!$this->isScenarioSelected($scenario, $routeFilters)) continue;

					$routeRef = $this->registerScenario($scenario);
					$routes[] = ['routeRef' => $routeRef];
				}
			}
		}

		return $routes;
	}

	/**
	 * Извлекает сценарии конкретного action'а из контроллера.
	 * Контракт: у каждого незаблокированного action'а должен существовать одноимённый testXxx() метод.
	 * Если метод отсутствует — возвращается skip-сценарий с пометкой TODO,
	 * чтобы можно было расширять покрытие инкрементально (см. tests/rest-todo.md).
	 */
	protected function getActionScenarios(BaseRestController $controller, string $actionId): array
	{
		$method = 'test' . Inflector::id2camel($actionId, '-');
		if (!method_exists($controller, $method)) {
			return [[
				'name' => 'no provider',
				'skip' => true,
				'reason' => "TODO: method $method() не определён в " . get_class($controller),
			]];
		}
		return $controller->$method();
	}

	/**
	 * Быстрая проверка фильтра на уровне controller/action до построения сценариев.
	 */
	protected function isActionSelected(string $controllerId, string $actionId, array $routeFilters): bool
	{
		if (empty($routeFilters)) return true;
		$controllerIdNorm = mb_strtolower(trim(StringHelper::class2Id($controllerId)));
		$actionIdNorm = mb_strtolower(trim($actionId));
		foreach ($routeFilters as $filter) {
			if ($filter['controller'] !== $controllerIdNorm) continue;
			if ($filter['action'] !== null && $filter['action'] !== $actionIdNorm) continue;
			return true;
		}
		return false;
	}

	/**
	 * Регистрирует сценарий в реестре и возвращает его ключ.
	 */
	protected function registerScenario(array $scenario): string
	{
		$baseRef = implode('/', [
			$scenario['controllerId'],
			$scenario['actionId'],
			$scenario['name'] ?? 'default',
		]);
		$routeRef = $baseRef;
		$i = 2;
		while (isset(static::$scenarioRegistry[$routeRef])) {
			$routeRef = $baseRef . '#' . $i;
			$i++;
		}
		static::$scenarioRegistry[$routeRef] = $scenario;
		return $routeRef;
	}

	/**
	 * Разбор TEST_REST_ROUTES: controller[/action[/scenario]], через запятую.
	 */
	protected function parseRouteFilters(string $raw): array
	{
		if ($raw === '') return [];
		$filters = [];
		foreach (explode(',', $raw) as $chunk) {
			$chunk = trim($chunk);
			if ($chunk === '') continue;
			[$controller, $action, $scenario] = array_pad(
				array_map(fn($v) => mb_strtolower(trim($v)), explode('/', $chunk, 3)),
				3,
				null
			);
			if (!$controller) continue;
			if ($action === '') $action = null;
			if ($scenario === '') $scenario = null;
			$filters[] = compact('controller', 'action', 'scenario');
		}
		return $filters;
	}

	/**
	 * Проверяет, подходит ли сценарий под TEST_REST_ROUTES.
	 */
	protected function isScenarioSelected(array $scenario, array $routeFilters): bool
	{
		if (empty($routeFilters)) return true;
		$controllerId = mb_strtolower(trim((string)$scenario['controllerId']));
		$actionId = mb_strtolower(trim((string)$scenario['actionId']));
		$scenarioName = mb_strtolower(trim((string)($scenario['name'])));
		foreach ($routeFilters as $filter) {
			if ($filter['controller'] !== $controllerId) continue;
			if ($filter['action'] !== null && $filter['action'] !== $actionId) continue;
			if ($filter['scenario'] !== null && $filter['scenario'] !== $scenarioName) continue;
			return true;
		}
		return false;
	}

	/**
	 * @dataProvider routesProvider
	 * @noRollback
	 */
	public function testAllEndpointsAccessible(ApiTester $I, \Codeception\Example $example)
	{
		$routeRef = $example['routeRef'] ?? null;
		Assert::assertNotEmpty($routeRef, 'routeRef is missing in data provider example');
		Assert::assertArrayHasKey($routeRef, static::$scenarioRegistry, "Scenario {$routeRef} is not registered");
		$example = static::$scenarioRegistry[$routeRef];

		if (!empty($example['skip'])) {
			Assert::markTestSkipped($example['reason'] ?? 'reason missing');
		}

		$method = strtoupper($example['method'] ?? 'GET');
		$route = $example['route'];
		$getParams = $example['GET'] ?? [];
		$body = $example['body'] ?? null;
		$code = $example['response'] ?? 200;
		$headers = $example['headers'] ?? [];
		// Значение по умолчанию для REST: JSON body.
		$I->haveHttpHeader('Content-Type', 'application/json');
		foreach ($headers as $headerName => $headerValue) {
			$I->haveHttpHeader($headerName, $headerValue);
		}

		$url = '/' . ltrim($route, '/');
		if (!empty($getParams)) {
			$url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($getParams);
		}

		$message = "$method $url";
		switch ($method) {
			case 'GET':      $I->sendGET($url); break;
			case 'POST':     $I->sendPOST($url, $body ?? []); break;
			case 'PUT':      $I->sendPUT($url, $body ?? []); break;
			case 'PATCH':    $I->sendPATCH($url, $body ?? []); break;
			case 'DELETE':   $I->sendDELETE($url); break;
			case 'OPTIONS':  $I->sendOPTIONS($url); break;
			default:
				Assert::fail("Unsupported HTTP method '$method' in scenario $routeRef");
		}

		$I->unsetHttpHeader('Content-Type');
		foreach ($headers as $headerName => $headerValue) {
			$I->unsetHttpHeader($headerName);
		}

		if (is_array($code)) {
			$I->seeResponseCodeIsBetween(min($code), max($code), $message);
		} else {
			$I->seeResponseCodeIs($code, $message);
		}

		if (isset($example['assert']) && is_callable($example['assert'])) {
			$example['assert']($I, $example, $route, $url);
		}
	}
}
