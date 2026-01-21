<?php

namespace app\swagger\processors;

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\HistoryModel;
use app\models\links\LicLinks;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Annotations\Property;
use ReflectionClass;
use Yii;
use yii\helpers\FileHelper;

/**
 * Задача сформировать описание (схемы) моделей ArmsModel для OpenAPI
 * Основные атрибуты - attributes()
 * Дополнительные атрибуты - extraFields() <- LinksSchema
 */

class GenerateModelSchemaProcessor
{
	/**
	 * Само добавление схем в анализ
	 * @param Analysis $analysis
	 * @return void
	 * @throws \ReflectionException
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function __invoke(Analysis $analysis): void
	{
	
		foreach ($this->apiModels() as $modelClass) {
			$model = new $modelClass();
			$class=StringHelper::basename($modelClass);
			
			$schemaRW = new OA\Schema([
				'schema' => (new ReflectionClass($modelClass))->getShortName().'(write)',
				'type' => 'object',
				'_context' => $analysis->context,
				'properties' => [],
			]);
			$schemaRO = new OA\Schema([
				'schema' => (new ReflectionClass($modelClass))->getShortName().'(read)',
				'type' => 'object',
				'_context' => $analysis->context,
				'properties' => [],
			]);

			//формируем набор атрибутов (из стандартных и расширенных)
			$attributes=array_unique(array_merge(
				$model->attributes(),
				$model->extraFields()
			));
			
			sort($attributes);
			foreach ($attributes as $attr) {
				$template=$model->generateRWAttributeAnnotation($attr);
				$descriptionRead=ArrayHelper::remove($template,'descriptionRead');
				$descriptionWrite=ArrayHelper::remove($template,'descriptionWrite');
				$template['_context']=$analysis->context;
				$templateRead=$template;
				$templateWrite=$template;
				$templateRead['description']=$descriptionRead;
				$templateWrite['description']=$descriptionWrite;
				if (!($template['writeOnly']??false))
					$schemaRO->properties[$attr] = new Property($templateRead);
				if (!($template['readOnly']??false))
					$schemaRW->properties[$attr] = new Property($templateWrite);
			}
			
			if ($analysis->openapi->components===Generator::UNDEFINED)
				$analysis->openapi->components=new OA\Components(['_context' => $analysis->context]);

			if ($analysis->openapi->components->schemas===Generator::UNDEFINED)
				$analysis->openapi->components->schemas=[];
			
			$schemaRO->description="Объект класса $class (поля доступные для чтения)";
			$schemaRW->description="Объект класса $class (поля доступные для записи)";
			$analysis->openapi->components->schemas[] = $schemaRW;
			$analysis->openapi->components->schemas[] = $schemaRO;
		}
	}
	
	/**
	 * Возвращает список моделей, схемы которых нужно сгенерировать для API
	 * @return array
	 */
	private function apiModels(): array
	{
		$list=[];
		foreach (FileHelper::findFiles(Yii::getAlias('@app/models'), [
			'only' => ['*.php'],
			'recursive'=>false
		]) as $file) {
			$modelClass = 'app\\models\\'
				.str_replace('/', '\\',
					StringHelper::removeSuffix(
						substr($file, strlen(Yii::getAlias('@app/models') . '/')),
						'.php'
					)
				);
			
			//пропускаем классы поиска и журналы изменений
			if (str_ends_with($modelClass, 'Search')) continue;
			if (str_ends_with($modelClass, 'History')) continue;

			//пропускаем несуществующие классы
			if (!class_exists($modelClass)) continue;
			
			//пропускаем базовые и служебные классы
			$className=StringHelper::className($modelClass);
			if (in_array($className,[
				'BaseRest',
				'ArmsModel',
				'HistoryModel',
				'LicLinks',
				'CompsRescanQueue'
			])) continue;
			
			if (!(new $modelClass) instanceof ArmsModel) continue;
			if ((new $modelClass) instanceof HistoryModel) continue;
			if ((new $modelClass) instanceof LicLinks) continue;
			
			$list[] = $modelClass;
		}
		return $list;
	}
	
}