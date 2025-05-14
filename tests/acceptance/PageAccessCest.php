<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use yii\base\InvalidConfigException;

class PageAccessCest
{
	
	public function _before(AcceptanceTester $I)
	{
	}
	
	protected function routesProvider()
	{
		$routes = [];
		$params=require __DIR__.'/../_data/get-routes-data.php';
		
		$controllerNamespace = 'app\\controllers';
		$controllerPath = Yii::getAlias('@app/controllers');
		
		//перебираем все файлы контроллеров
		foreach (scandir($controllerPath) as $file) {
			if (preg_match('/([A-Za-z0-9]+)Controller\.php$/', $file, $matches)) {
				$controllerId = lcfirst(str_replace('Controller', '', $matches[1]));
				$controllerClass = "$controllerNamespace\\{$matches[1]}Controller";
				
				if (!class_exists($controllerClass)) continue;
				$controller = new $controllerClass($controllerId, Yii::$app);
				
				$model=null;
				if (property_exists($controller,'modelClass')) {
					// Если контроллер имеет атрибут modelClass, то получаем его
					$modelClass = $controller->modelClass;
					if ($modelClass)
						$model=$modelClass::find()->one();
				}
				
				$actions=array_keys($controller->actions());
				
				// Получаем методы самого контроллера
				$reflection = new \ReflectionClass($controllerClass);
				foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
					if (preg_match('/^action([A-Z].+)/', $method->name, $actionMatch)) {
						$actions[]=lcfirst($actionMatch[1]);
					}
				}
				
				foreach ($actions as $action) {
					$actionId=StringHelper::class2Id($action);
					
					// Проверяем, разрешён ли GET
					$verbs = $controller->behaviors()['verbs']['actions'][$actionId] ?? ['GET'];
					if (!in_array('GET', $verbs)) continue;
					
					$route=StringHelper::class2Id($controllerId).'/'.$actionId;
					
					$routeParams=ArrayHelper::findByRegexKey($params,$route,'');
					
					if ($routeParams==='{skipTest}') continue;
					if (str_contains($routeParams,'{anyId}')) {
						if (is_null($model))
							throw new InvalidConfigException($controllerClass.' error loading sample model for '.$action);
						$routeParams=str_replace('{anyId}',$model->id,$routeParams);
					}
					
					if (str_contains($routeParams,'{anyName}')) {
						if (is_null($model))
							throw new InvalidConfigException($controllerClass.' error loading sample model for '.$action);
						$routeParams=str_replace('{anyName}',$model->name,$routeParams);
					}
					
					if ($routeParams) $route.=$routeParams;
					
					$routes[] = ['route'=>$route];
				}
				
				
			}
		}
		return $routes;
	}
	
	/**
	 * @dataProvider routesProvider
	 * @return void
	 */
	public function testAllGetRoutesAccessible(AcceptanceTester $I, \Codeception\Example $example)
	{
		$route=$example['route'];
		$I->amOnPage("/$route");
		$I->seeResponseCodeIs(200,"GET $route is accessible");
	}
}
