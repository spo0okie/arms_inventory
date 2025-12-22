<?php

namespace app\components\swagger;

use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\HistoryModel;
use app\models\links\LicLinks;
use OpenApi\Annotations as OA;
use OpenApi\Analysis;
use OpenApi\Generator;
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
			
			$schema = new OA\Schema([
				'schema' => (new ReflectionClass($modelClass))->getShortName(),
				'type' => 'object',
				'_context' => $analysis->context,
				'properties' => [],
			]);
			
			$attributes=$model->attributes();
			sort($attributes);
			foreach ($attributes as $attr) {
				$schema->properties[$attr] = $model->generateAttributeAnnotation($attr,$analysis->context);
			}
			
			// Обработка extraFields
			$extraFields = $model->extraFields();
			if (!empty($extraFields)) {
				foreach ($extraFields as $field) {
					// Пропускаем поля, которые уже есть в основных атрибутах
					if (in_array($field, $attributes)) continue;
					
					// Генерируем аннотацию для дополнительного поля
					$schema->properties[$field] = $model->generateAttributeAnnotation($field, $analysis->context);
				}
			}
			
			if ($analysis->openapi->components===Generator::UNDEFINED)
				$analysis->openapi->components=new OA\Components(['_context' => $analysis->context]);

			if ($analysis->openapi->components->schemas===Generator::UNDEFINED)
				$analysis->openapi->components->schemas=[];

			$analysis->openapi->components->schemas[] = $schema;
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