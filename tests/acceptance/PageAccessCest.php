<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use yii\base\InvalidConfigException;

class PageAccessCest
{
	
	public function _before(AcceptanceTester $I)
	{
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
	 * @param array $params
	 * @param array $models
	 * @return void
	 */
	protected function templateRouteParams(&$params,$models)
	{
		foreach ($params as $verb=>$verbParams) {
			foreach ($verbParams as $param=>$value) {
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
				}
			}
		}
	}
	
	protected function routesProvider()
	{
		$routes = [];
		$params=require __DIR__.'/../_data/get-routes-data.php';
		
		//перебираем все файлы контроллеров
		foreach (scandir(Yii::getAlias('@app/controllers')) as $file) {
			codecept_debug($file);
			$controller=$this->getController($file);
			if (is_object($controller)) {
				$models=$this->getModels($controller);
				
				foreach ($this->getActions($controller) as $action) {
					$actionId=StringHelper::class2Id($action);
					
					// Проверяем, разрешён ли GET
					$verbs = $controller->behaviors()['verbs']['actions'][$actionId] ?? ['GET'];
					if (!in_array('GET', $verbs)) continue;
					
					$route=StringHelper::class2Id($controller->id).'/'.$actionId;
					
					$routeParams=ArrayHelper::findByRegexKey($params,$route,[]);
					
					if ($routeParams==='{skipTest}') continue;
					
					try {
						$this->templateRouteParams($routeParams,$models);
					} catch (InvalidConfigException $e) {
						//дополняем информацию маршрутом на котором произошла ошибка
						throw new InvalidConfigException("Error in route '$route': ".$e->getMessage());
					}
					
					$routeParams['route']=$route;
					$routes[] = $routeParams;
				}
				
				
			}
		}
		return $routes;
	}
	
	/**
	 * @dataProvider routesProvider
	 * @return void
	 */
	public function testAllRoutesAccessible(AcceptanceTester $I, \Codeception\Example $example)
	{
		$route=$example['route'];
		$getParams=$example['GET']??[];
		$getParams[0]='/'.$route;
		$route=\yii\helpers\Url::toRoute($getParams);

		$postParams=$example['POST']??[];
		$I->amOnPage('/web'.$route);
		$I->seeResponseCodeIs(200,"GET $route is accessible");
	}
}
