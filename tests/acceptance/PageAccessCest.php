<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use PHPUnit\Framework\Assert;
use yii\helpers\Inflector;
use yii\base\InvalidConfigException;

class PageAccessCest
{
	/**
	 * Реестр сценариев, собранных в routesProvider().
	 * Ключ: controller/action/scenario
	 * Значение: полный сценарий для исполнения теста.
	 *
	 * @var array<string, array>
	 */
	protected static array $scenarioRegistry = [];
	
	public function _failed($test, $fail)
	{
		//Helper\Acceptance::$testsFailed = true;
	}
	
	/**
	 * Возвращает объект контроллера по имени файла
	 * и reflectionClass контроллера
	 * @param $file
	 * @param $moduleName
	 * @return ArmsBaseController|null
	 */
	protected function getController($file, $moduleName = null)
	{
		if ($moduleName) {
			$controllerNamespace = "app\\modules\\{$moduleName}\\controllers";
		} else {
			$controllerNamespace = 'app\\controllers';
		}
		
		if (preg_match('/([A-Za-z0-9]+)Controller\.php$/', $file, $matches)) {
			$controllerName = str_replace('Controller', '', $matches[1]);
			$controllerId = lcfirst($controllerName);
			$controllerClass = "$controllerNamespace\\{$matches[1]}Controller";
			
			if (class_exists($controllerClass)) return new $controllerClass($controllerId, Yii::$app);
		}
		return null;
	}
	
	/**
	 * Возвращает список actions контроллера
	 * @param $controller
	 * @return int[]|string[]
	 */
	protected function getActions($controller)
	{
		$actions=array_keys($controller->actions());
		$reflection=new ReflectionClass(get_class($controller));
		// Получаем методы самого контроллера
		foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if (preg_match('/^action([A-Z].+)/', $method->name, $actionMatch)) {
				$actions[]=lcfirst($actionMatch[1]);
			}
		}
		return $actions;
	}
	

	
	/**
	 * Подготавливает маршруты, которые есть у приложения чтобы их протестировать
	 * Эта функция через phpDoc указана у теста testAllRoutesAccessible как dataProvider
	 * И по логике codeception она вызывается до _beforeSuite
	 * А нам в ней нужно рабочее приложение с развернутой тестовой БД
	 * поэтому инициализации приложения и его БД перенесено в эту функцию
	 * 
	 * Опциональный параметр "class" ограничивает тестирование определенным классом модели.
	 * Если передан параметр, будут протестированы только маршруты для этого класса.
	 * 
	 * @return array
	 * @throws InvalidConfigException
	 */
	protected function routesProvider()
	{
		$routes = [];
		static::$scenarioRegistry = [];
		Helper\Yii2::initFromFilename('test-web.php');
		codecept_debug('Initializing Suite DB...');
		//Подготавливаем временную БД
		Helper\Database::dropYiiDb();
		Helper\Database::prepareYiiDb();
		Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');

		
		// Получаем опциональный фильтр по классу модели из окружения (если передан)
		$routeFilters = $this->parseRouteFilters(getenv('TEST_ROUTES') ?: '');
		
		// Сканируем основные контроллеры
		$this->scanControllers(__DIR__.'/../../controllers', null, $routes, $routeFilters);
		
		// Сканируем контроллеры модулей
		$modulesDir = __DIR__.'/../../modules';
		if (is_dir($modulesDir)) {
			foreach (scandir($modulesDir) as $moduleName) {
				if ($moduleName === '.' || $moduleName === '..') continue;
				$moduleControllersDir = $modulesDir . '/' . $moduleName . '/controllers';
				if (is_dir($moduleControllersDir)) {
					$this->scanControllers($moduleControllersDir, $moduleName, $routes, $routeFilters);
				}
			}
		}
		
		return $routes;
	}

	
	/**
	 * Сканирует директорию контроллеров и добавляет маршруты в массив
	 * @param string $controllersDir Директория с контроллерами
	 * @param string|null $moduleName Имя модуля (null для основных контроллеров)
	 * @param array &$routes Массив маршрутов для добавления
	 * @return void
	 * @throws InvalidConfigException
	 */
	protected function scanControllers($controllersDir, $moduleName, &$routes, array $routeFilters = [])
	{
		foreach (scandir($controllersDir) as $file) {
			//codecept_debug($file);
			$controller=$this->getController($file, $moduleName);
			if (is_object($controller)) {
				if (!$controller instanceof \app\controllers\ArmsBaseController) {
					continue;
				}
				if (get_class($controller) === \app\controllers\ArmsBaseController::class) {
					continue;
				}
				
				foreach ($this->getActions($controller) as $action) {
					$actionId=StringHelper::class2Id($action);
					codecept_debug($file.'/'.$actionId);
					$testDisabled = $controller->disabledTests();
					if (
						in_array('*', $testDisabled, true) ||
						in_array($actionId, $testDisabled, true)
					){
						continue;
					}
					
					if (in_array($actionId, $controller->disabledActions(), true)) {
						continue;
					}

					if (!$this->isActionSelected($controller->id, $actionId, $routeFilters)) {
						continue;
					}
					
					// Проверяем, разрешён ли GET
					$verbs = $controller->behaviors()['verbs']['actions'][$actionId] ?? ['GET'];
					if (!count($verbs)) continue; //если получили [] - значит действие отключено и всегда будет возвращать 405
					
					$scenarios = $this->getActionScenarios($controller, $actionId);
					foreach ($scenarios as $scenario) {
						$controllerId = StringHelper::class2Id($controller->id);
						$route = $scenario['route'] ?? '{controller}/{action}';
						$route = str_replace(['{controller}', '{action}'], [$controllerId, $actionId], $route);
						$scenario['route'] = $route;
						$scenario['controller'] = $file;
						$scenario['moduleName'] = $moduleName;
						$scenario['controllerId'] = $controllerId;
						$scenario['actionId'] = $actionId;
						if (!isset($scenario['name'])) $scenario['name']='default';

						if (!$this->isScenarioSelected($scenario, $routeFilters)) {
							continue;
						}

						$routeRef = $this->registerScenario($scenario);
						$routes[] = ['routeRef' => $routeRef];
					}
				}
				
			}
		}
	}

	/**
	 * Быстрая проверка фильтра на уровне controller/action до построения сценариев.
	 *
	 * @param string $controllerId
	 * @param string $actionId
	 * @param array $routeFilters
	 * @return bool
	 */
	protected function isActionSelected(string $controllerId, string $actionId, array $routeFilters): bool
	{
		if (empty($routeFilters)) {
			return true;
		}
		$normalize = static function (string $value): string {
			return mb_strtolower(trim($value));
		};
		$controllerIdNorm = $normalize(StringHelper::class2Id($controllerId));
		$actionIdNorm = $normalize($actionId);
		foreach ($routeFilters as $filter) {
			if ($normalize($filter['controller']) !== $controllerIdNorm) {
				continue;
			}
			if ($filter['action'] !== null && $normalize($filter['action']) !== $actionIdNorm) {
				continue;
			}
			return true;
		}
		return false;
	}

	/**
	 * Регистрирует сценарий в локальном реестре и возвращает его ключ.
	 *
	 * @param array $scenario
	 * @return string
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
	 * Разбирает фильтр TEST_ROUTES в массив правил.
	 *
	 * Поддерживаемые форматы (через запятую):
	 * - controller
	 * - controller/action
	 * - controller/action/scenario name
	 *
	 * @param string $raw
	 * @return array<int, array{controller:string,action:?string,scenario:?string}>
	 */
	protected function parseRouteFilters(string $raw): array
	{
		if ($raw === '') {
			return [];
		}
		$filters = [];
		foreach (explode(',', $raw) as $chunk) {
			$chunk = trim($chunk);
			if ($chunk === '') {
				continue;
			}
			[$controller, $action, $scenario] = array_pad(
				array_map(
					fn($v) => mb_strtolower(trim($v)),
					explode('/', $chunk, 3)
				),
				3,
				null
			);
			if (!$controller) {
				continue;
			}
			if ($action==='') $action = null;
			if ($scenario==='') $scenario = null;
			$filters[] = compact('controller', 'action', 'scenario');
		}
		return $filters;
	}

	/**
	 * Проверяет, подходит ли сценарий под TEST_ROUTES.
	 *
	 * @param array $scenario
	 * @param array $routeFilters
	 * @return bool
	 */
	protected function isScenarioSelected(array $scenario, array $routeFilters): bool
	{
		if (empty($routeFilters)) {
			return true;
		}

		$controllerId = mb_strtolower(trim((string)$scenario['controllerId']));
		$actionId = mb_strtolower(trim((string)$scenario['actionId']));
		$scenarioName = mb_strtolower(trim((string)($scenario['name'])));
		foreach ($routeFilters as $filter) {
			if ($filter['controller'] !== $controllerId) {
				continue;
			}
			if ($filter['action'] !== null && $filter['action'] !== $actionId) {
				continue;
			}
			if ($filter['scenario'] !== null && $filter['scenario'] !== $scenarioName) {
				continue;
			}
			return true;
		}
		return false;
	}
	
	protected function getActionScenarios($controller, string $actionId): array
	{
		$method = 'test' . Inflector::id2camel($actionId, '-');
		return $controller->$method();

	}
	/**
	 * @dataProvider routesProvider
	 * @return void
	 * @noRollback
	 */
	public function testAllRoutesAccessible(AcceptanceTester $I, \Codeception\Example $example)
	{
		$routeRef = $example['routeRef'] ?? null;
		Assert::assertNotEmpty($routeRef, 'routeRef is missing in data provider example');
		Assert::assertArrayHasKey($routeRef, static::$scenarioRegistry, "Scenario {$routeRef} is not registered");
		$example = static::$scenarioRegistry[$routeRef];

		if (!empty($example['skip'])) {
			Assert::markTestSkipped($example['reason'] ?? 'reason missing');
		}
		
		$I->stopFollowingRedirects();
		$route=$example['route'];
		
		$getParams=$example['GET']??[];
		$getParams[0]='/'.$route;
		$fullRoute=\yii\helpers\Url::toRoute($getParams);
		
		$postParams=$example['POST']??null;
		$code=$example['response']??200;
		
		
		$message='';
		if (is_null($postParams)) {
			$I->amOnPage($fullRoute);
			$message="GET $route is accessible";
			
		} else {
			$I->sendPOST($fullRoute, $postParams);
			$message="POST data $fullRoute is nominal";
		}

		if (is_array($code))
			$I->seeResponseCodeIsBetween(min($code),max($code),$message);
		else
			$I->seeResponseCodeIs($code,$message);
		
		if (isset($example['assert']) && is_callable($example['assert'])) {
			$example['assert']($I, $example, $route, $fullRoute);
		}
	}
}
