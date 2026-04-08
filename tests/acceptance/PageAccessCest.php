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
	 * Загружает пару моделей для контроллера
	 * @param $controller
	 * @return \app\models\ArmsModel[]
	 */
	protected function getModels($controller)
	{
		$model=null;
		$otherModel=null;
		codecept_debug('getModels for controller: '.get_class($controller));
		if (property_exists($controller,'modelClass')) {
			// Если контроллер имеет атрибут modelClass, то получаем его
			$modelClass = $controller->modelClass;
			codecept_debug('modelClass: '.$modelClass);
			if ($modelClass) {
				$model=$modelClass::find()->one();
				if (is_object($model)) {
					codecept_debug('model: '.$model->id);
					$otherModel=$modelClass::find()->where(['not',['id'=>$model->id]])->one();
				}
			}
		}
		return [$model,$otherModel];
	}
	
	protected function getModel($controller,$params)
	{
		codecept_debug('getModels for controller: '.get_class($controller));
		if (property_exists($controller,'modelClass')) {
			// Если контроллер имеет атрибут modelClass, то получаем его
			$modelClass = $controller->modelClass;
			codecept_debug('modelClass: '.$modelClass);
			if ($modelClass) {
				return $modelClass::find()->where($params)->one();
			}
		}
		return null;
	}
	
	protected function dropReverseLinks($model)
	{
		if (is_null($model)) return;
		$needUpdate=false;
		foreach ($model->getLinksSchema() as $attribute=>$data) {
			if (StringHelper::endsWith($attribute,'_ids')) {
				codecept_debug('Dropping '.get_class($model).' reverse link: '.$attribute);
				$model->$attribute=[];
				$needUpdate=true;
			}
		}
		if ($needUpdate) {
			codecept_debug('Dropping reverse links for model: '.get_class($model).' id: '.$model->id);
			if (!$model->save(false)) {
				throw new InvalidConfigException('Error saving model after dropping reverse links: '.print_r($model->getErrors(),true));
			}
			codecept_debug('Dropping reverse links for model: '.get_class($model).' id: '.$model->id.' SUCCESS');
		}
	}
	
	/**
	 * Наполняет шаблоны в параметрах значениями
	 *
	 * @param array $models
	 * @return void
	 * @throws InvalidConfigException
	 */
	protected function templateRouteParams(&$params,$models)
	{
		foreach ($params as $verb=>$verbParams) {
			if (is_array($verbParams)) foreach ($verbParams as $param=>$value) {
				switch ($value) {
					case '{anyId}':
						if (is_null($models[0]))
							throw new InvalidConfigException('error loading single model');
						$params[$verb][$param]=$models[0]->id;
						break;
					case '{otherId}':
						if (is_null($models[1]))
							throw new InvalidConfigException('error loading second model');
						$params[$verb][$param]=$models[1]->id;
						break;
					case '{anyName}':
						if (is_null($models[0]))
							throw new InvalidConfigException('error loading single model');
						$params[$verb][$param]=$models[0]->name;
						break;
					case '{anyModelParams}':
						if (is_null($models[0]))
							throw new InvalidConfigException('error loading single model');
						unset($params[$verb][$param]); //убираем параметр с макросом, так как его заменяем множеством параметров
						$params[$verb][StringHelper::className(get_class($models[0]))]=Helper\ModelData::fillForm(
							Helper\ModelData::getFormAttributes($models[0]),
							$models[0]);
						break;
					case '{otherModelParams}':
						if (is_null($models[1]))
							throw new InvalidConfigException('error loading second model');
						unset($params[$verb][$param]); //убираем параметр с макросом, так как его заменяем множеством параметров
						$params[$verb][StringHelper::className(get_class($models[1]))]=Helper\ModelData::fillForm(
							Helper\ModelData::getFormAttributes($models[1]),
							$models[1]
						);
						break;
					default:
						if (is_string($value) && preg_match('/{(\\w+)ModelParams}/', $value, $matches)) {
							//если в значении параметра есть макрос, то заменяем его на параметры модели
							$name=$matches[1];
							if (isset($models[$name])) {
								$model=$models[$name];
								$params[$verb][StringHelper::className(get_class($model))]=Helper\ModelData::fillForm(
									Helper\ModelData::getFormAttributes($model),
									$model
								);
							} else {
								throw new InvalidConfigException("Error loading model '$name' in route params");
							}
						}
						if (is_array($value)) {
							$this->templateRouteParams($params[$verb], $models);
						}
				}
			}
		}
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

	protected function extractVariantIndex(string $route): int
	{
		if (preg_match('/\\[(\\d+)\\]$/', $route, $m)) {
			return (int)$m[1];
		}
		return 0;
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
						$defaultRoute = $controllerId.'/'.$actionId;
						$route = $scenario['route'] ?? $defaultRoute;
						$route = str_replace(['{controllerId}', '{action}'], [$controllerId, $actionId], $route);
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
		/*return [
			[
				'name' => 'auto-skip',
				'skip' => true,
				'reason' => "No DataProvider method $method",
			],
		];*/
	}
	/**
	 * @dataProvider routesProvider
	 * @return void
	 * @noRollback
	 */
	public function testAllRoutesAccessible(AcceptanceTester $I, \Codeception\Example $example)
	{
		if (!empty($example['skip'])) {
			Assert::markTestSkipped($example['reason'] ?? 'skipped');
		}
		
		$I->stopFollowingRedirects();
		$route=$example['route'];
		$controller=$this->getController($example['controller'], $example['moduleName'] ?? null);
		$modelClass=$controller->modelClass;
		if (!isset($this->savedModels[$modelClass])) $this->savedModels[$modelClass]=[];
		
		$models=\yii\helpers\ArrayHelper::merge(
			$this->getModels($controller),$this->savedModels[$modelClass]
		);
		
		$routeParams=(array)$example->getIterator();
		/*try {
			$this->templateRouteParams($routeParams, $models);
		} catch (InvalidConfigException $e) {
			//дополняем информацию маршрутом на котором произошла ошибка
			throw new InvalidConfigException("Error in route '$route': " . $e->getMessage());
		}*/

		if (!is_null($saveModel=$routeParams['saveModel']??null)) {
			$this->savedModels[$modelClass][$saveModel['storeAs']]=$this->getModel($controller,$saveModel['model']);
		}
		
		if (!is_null($dropReverseLinks=$routeParams['dropReverseLinks']??null)) {
			$this->dropReverseLinks($this->getModel($controller,$dropReverseLinks));
		}
		
		$getParams=$routeParams['GET']??[];
		$getParams[0]='/'.$route;
		$route=\yii\helpers\Url::toRoute($getParams);
		
		$postParams=$routeParams['POST']??null;
		$code=$example['response']??200;
		
		
		$message='';
		if (is_null($postParams)) {
			$I->amOnPage($route);
			$message="GET $route is accessible";
			
		} else {
			$I->sendPOST($route, $postParams);
			$message="POST data $route is nominal";
		}

		if (is_array($code))
			$I->seeResponseCodeIsBetween(min($code),max($code),$message);
		else
			$I->seeResponseCodeIs($code,$message);
	}
}
