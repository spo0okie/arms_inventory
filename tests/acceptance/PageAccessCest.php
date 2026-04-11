<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use PHPUnit\Framework\Assert;
use yii\helpers\Inflector;
use yii\base\InvalidConfigException;

class PageAccessCest
{
	
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
		Helper\Yii2::initFromFilename('test-web.php');
		codecept_debug('Initializing Suite DB...');
		//Подготавливаем временную БД
		Helper\Database::dropYiiDb();
		Helper\Database::prepareYiiDb();
		Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');

		
		// Получаем опциональный фильтр по классу модели из окружения (если передан)
		$classFilter = getenv('TEST_CLASS_FILTER') ?: null;
		
		// Сканируем основные контроллеры
		$this->scanControllers(__DIR__.'/../../controllers', null, $routes, $classFilter);
		
		// Сканируем контроллеры модулей
		$modulesDir = __DIR__.'/../../modules';
		if (is_dir($modulesDir)) {
			foreach (scandir($modulesDir) as $moduleName) {
				if ($moduleName === '.' || $moduleName === '..') continue;
				$moduleControllersDir = $modulesDir . '/' . $moduleName . '/controllers';
				if (is_dir($moduleControllersDir)) {
					$this->scanControllers($moduleControllersDir, $moduleName, $routes, $classFilter);
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
	 * @param string|null $classFilter Опциональный фильтр по классу модели (имя класса, например "Comps")
	 * @return void
	 * @throws InvalidConfigException
	 */
	protected function scanControllers($controllersDir, $moduleName, &$routes, $classFilter = null)
	{
		foreach (scandir($controllersDir) as $file) {
			codecept_debug($file);
			$controller=$this->getController($file, $moduleName);
			if (is_object($controller)) {
				if (!$controller instanceof \app\controllers\ArmsBaseController) {
					continue;
				}
				if (get_class($controller) === \app\controllers\ArmsBaseController::class) {
					continue;
				}
				
				// Если задан фильтр по классу, пропускаем контроллеры с другим классом модели
				if ($classFilter !== null) {
					$modelClass = $controller->modelClass ?? '';
					// Получаем имя класса из полного пути (например "app\models\Comps" -> "Comps")
					$modelClassName = substr(strrchr($modelClass, '\\'), 1) ?: $modelClass;
					if ($modelClassName !== $classFilter) {
						codecept_debug("Skipping controller with modelClass: $modelClass (filter: $classFilter)");
						continue;
					}
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
						$routes[] = $scenario;
					}
				}
				
			}
		}
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
	}
}
