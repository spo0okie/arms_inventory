<?php

namespace app\components\swagger;

use app\helpers\StringHelper;
use app\models\ArmsModel;
use OpenApi\Annotations as OA;
use OpenApi\Analysis;
use OpenApi\Generator;
use yii\helpers\FileHelper;

/*
 * Задача сформировать описание моделей ArmsModel для OpenAPI
 * Основные атрибуты - attributes()
 * Дополнительные атрибуты - extraFields()
 */

class ArmsModelSchemaProcessor
{
	public function __invoke(Analysis $analysis)
	{
	
		foreach ($this->apiModels() as $modelClass) {
			$model = new $modelClass();
			if (! $model instanceof ArmsModel) {
				continue;
			}
			
			$schema = new OA\Schema([
				'schema' => (new \ReflectionClass($modelClass))->getShortName(),
				'type' => 'object',
				'_context' => $analysis->context,
				'properties' => [],
			]);
			
			$attributes=$model->attributes();
			sort($attributes);
			foreach ($attributes as $attr) {
				$schema->properties[$attr] = $model->generateAttributeAnnotation($attr,$analysis->context);
			}
			
			if ($analysis->openapi->components===Generator::UNDEFINED)
				$analysis->openapi->components=new OA\Components(['_context' => $analysis->context]);
			if ($analysis->openapi->components->schemas===Generator::UNDEFINED)
				$analysis->openapi->components->schemas=[];
			$analysis->openapi->components->schemas[] = $schema;
			//$analysis->addAnnotation($schema, $analysis->context);
			//$analysis->openapi->merge([$schema], true);
		}
	}
	
	private function apiModels()
	{
		$list=[];
		/*foreach (FileHelper::findFiles(\Yii::getAlias('@app/modules/api/controllers/'), ['only' => ['*.php']]) as $file) {
			$controllerName=basename($file, '.php');
			$modelName=StringHelper::removeSuffix($controllerName, 'Controller');
			if ($modelName==='BaseRest') continue;
			$list[]='app\models\\'.$modelName;
		}*/
		foreach (FileHelper::findFiles(\Yii::getAlias('@app/models'), ['only' => ['*.php']]) as $file) {
			$modelClass = 'app\\models\\'
				.str_replace('/', '\\',
					StringHelper::removeSuffix(
						substr($file, strlen(\Yii::getAlias('@app/models') . '/')),
						'.php'
					)
				);
			if (StringHelper::className($modelClass)==='BaseRest') continue;
			
			if (!class_exists($modelClass)) {
				continue;
			}
			
			$controllerName = StringHelper::basename($modelClass) . 'Controller';
			
			if ($controllerName==='LicLinksController') continue;
			
			$controllerPath = \Yii::getAlias('@app/modules/api/controllers/' . $controllerName . '.php');
			if (file_exists($controllerPath)) {
				$list[] = $modelClass;
			}
		}
		return $list;
	}
	
}