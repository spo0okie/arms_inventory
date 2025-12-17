<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use yii\base\InvalidConfigException;

class PageAccessCest
{
	public $savedModels=[];
	public $rootDb;
	
	public function _failed($test, $fail)
	{
		Helper\Acceptance::$testsFailed = true;
	}
	
	/**
	 * Возвращает объект контроллера по имени файла
	 * и reflectionClass контроллера
	 * @param $file
	 * @return array
	 */
	protected function getController($file)
	{
		$controllerNamespace = 'app\\controllers';
		if (preg_match('/([A-Za-z0-9]+)Controller\.php$/', $file, $matches)) {
			$controllerId = lcfirst(str_replace('Controller', '', $matches[1]));
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
	 * Возвращает список атрибутов, которые надо заполнять в форме для модели
	 * @param \app\models\ArmsModel $model
	 * @return array
	 */
	protected function getFormAttributes($model)
	{
		$attributes=$model->attributes();
		foreach ($model->getLinksSchema() as $attribute => $schema) {
			// Если атрибут заканчивается на _ids, то это точно не junction_table
			if (!StringHelper::endsWith($attribute,'_ids')) continue;
			//проверяем обратную ссылку
			$reverseLink=$model->attributeReverseLink($attribute);
			//она должна тоже заканчиваться на _ids
			if (!$reverseLink) continue;
			if (!StringHelper::endsWith($reverseLink,'_ids')) continue;
			//это junction_table атрибут и его тоже надо в форме выводить
			codecept_debug($attribute.' is junction_table attribute with ' .$schema[0].'::'.$reverseLink);
			$attributes[]=$attribute;
		}
		return $attributes;
	}
	
	protected function fillForm($attrs,$model,$skip=['id'])
	{
		$form=[];
		foreach ($attrs as $attribute) {
			if (in_array($attribute,$skip)) continue;
			$form[$attribute]=$model->$attribute;
		}
		return $form;
	}
	
	/**
	 * Наполняет шаблоны в параметрах значениями
	 *
	 * @param array $params
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
						unset($params[$verb][$param]); //убираем параметр с макросом, так как его заменяем множество параметров
						$params[$verb][StringHelper::className(get_class($models[0]))]=$this->fillForm($this->getFormAttributes($models[0]),$models[0]);
						break;
					case '{otherModelParams}':
						if (is_null($models[1]))
							throw new InvalidConfigException('error loading second model');
						unset($params[$verb][$param]); //убираем параметр с макросом, так как его заменяем множество параметров
						$params[$verb][StringHelper::className(get_class($models[1]))]=$this->fillForm($this->getFormAttributes($models[1]),$models[1]);
						break;
					default:
						if (is_string($value) && preg_match('/{(\w+)ModelParams}}/', $value, $matches)) {
							//если в значении параметра есть макрос, то заменяем его на параметры модели
							$name=$matches[1];
							if (isset($models[$name])) {
								$model=$models[$name];
								$params[$verb][StringHelper::className(get_class($model))]=$this->fillForm($this->getFormAttributes($model),$model);
							} else {
								throw new InvalidConfigException("Error loading model '$model' in route params");
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
	 * @return array
	 * @throws InvalidConfigException
	 */
	protected function routesProvider()
	{
		$routes = [];
		$params=require __DIR__.'/../_data/get-routes-data.php';
		Helper\Yii2::initFromFilename('test-web.php');
		codecept_debug('Initializing Suite DB...');
		//Подготавливаем временную БД
		Helper\Database::dropYiiDb();
		Helper\Database::prepareYiiDb();
		Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');
		
		//перебираем все файлы контроллеров
		foreach (scandir(__DIR__.'/../../controllers') as $file) {
			codecept_debug($file);
			$controller=$this->getController($file);
			if (is_object($controller)) {
				if (!$controller instanceof \app\controllers\ArmsBaseController) {
					continue;
				}
				
				foreach ($this->getActions($controller) as $action) {
					$actionId=StringHelper::class2Id($action);
					
					// Проверяем, разрешён ли GET
					$verbs = $controller->behaviors()['verbs']['actions'][$actionId] ?? ['GET'];
					if (!count($verbs)) continue;	//если получили [] - значит действие отключено и всегда будет возвращать 405
					
					$route=StringHelper::class2Id($controller->id).'/'.$actionId;
					
					
					$variant=0;
					
					while (!is_null($routeParams=ArrayHelper::findByRegexKey(
						$params,
						$route.($variant?"[$variant]":''),	//если это не нулевой вариант, то дописываем суффикс[N]
						$variant?null:[]							//если это нулевой вариант, то по умолчанию он может быть и без параметров
					))) {
						
						//codecept_debug($route.($variant?"[$variant]":''));
						//codecept_debug(print_r($routeParams,true));
						
						if ($routeParams === '{skipTest}') continue 2;
						
						$routeParams['route'] = $route;
						$routeParams['controller'] = $file;
						$routes[] = $routeParams;
						
						$variant++;
					}
				}
				
				
			}
		}
		usort($routes, function ($a, $b) {
			$priority = ['delete', 'validate', 'update', 'create', 'view'];
			[$controllerA,$actionA]=explode('/', $a['route']);
			[$controllerB,$actionB]=explode('/', $b['route']);
			
			if ($controllerA!==$controllerB) return $controllerA<=>$controllerB;
			
			$indexA = array_search($actionA, $priority);
			$indexB = array_search($actionB, $priority);
			
			// Если окончание не найдено, ставим в конец
			$indexA = $indexA !== false ? $indexA : PHP_INT_MAX;
			$indexB = $indexB !== false ? $indexB : PHP_INT_MAX;
			
			return $indexA <=> $indexB;
		});
		return $routes;
	}
	
	/**
	 * @dataProvider routesProvider
	 * @return void
	 * @noRollback
	 */
	public function testAllRoutesAccessible(AcceptanceTester $I, \Codeception\Example $example)
	{
		$I->stopFollowingRedirects();
		$route=$example['route'];
		$controller=$this->getController($example['controller']);
		$modelClass=$controller->modelClass;
		if (!isset($this->savedModels[$modelClass])) $this->savedModels[$modelClass]=[];
		
		$models=\yii\helpers\ArrayHelper::merge(
			$this->getModels($controller),$this->savedModels[$modelClass]
		);
		
		$routeParams=(array)$example->getIterator();
		try {
			$this->templateRouteParams($routeParams, $models);
		} catch (InvalidConfigException $e) {
			//дополняем информацию маршрутом на котором произошла ошибка
			throw new InvalidConfigException("Error in route '$route': " . $e->getMessage());
		}

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
