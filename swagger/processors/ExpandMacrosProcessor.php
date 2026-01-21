<?php
namespace app\swagger\processors;

use app\helpers\StringHelper;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

class ExpandMacrosProcessor
{
	public function __invoke(Analysis $analysis): void
	{
		foreach ($analysis->annotations as $annotation) {
			$ctx = $annotation->_context??null; // контекст аннотации
			
			//Разгребаем операции (GET/PUT/POST/DELETE и т.д.)
			if ($annotation instanceof OA\Operation) {
				if ($ctx && $ctx->method && $ctx->class) {
					// Получаем имя контроллера и метода
					$controllerClass = $ctx->class;
					$methodName = $ctx->method;
					
					// Только для контроллеров
					if (str_ends_with($controllerClass, 'Controller')) {
						
						// Если path пустой — проставляем
						if (empty($annotation->path)) {
							$annotation->path = '/{controller}/{action}';
						}
						
						// Если теги пустые — проставляем
						if (empty($annotation->tags) || $annotation->tags === Generator::UNDEFINED) {
							$annotation->tags = ['{controller}'];
						}
						
						// Раскрываем макросы:
						// В путях: {controller}, {action}, {model}, {model->titles}
						$annotation->path = static::macroSubstitute($annotation->path, $controllerClass,$methodName);
						
						// В тегах:
						$tags=[];
						foreach ($annotation->tags as $tag)
							$tags[] = static::macroSubstitute($tag, $controllerClass,$methodName);
						$annotation->tags = $tags;
						
						// Раскрываем макро-параметр {searchFields} в параметрах операции (метода контроллера)
						static::expandSearchFields($annotation,$analysis);
					}
				}
			}
			
			if ($annotation instanceof \OpenApi\Annotations\Tag) {
				if ($ctx && $ctx->class) {
					$controllerClass = $ctx->class;
					if (str_ends_with($controllerClass, 'Controller')) {
						// Если name пустой — проставляем
						if (empty($annotation->name)) {
							$annotation->name = '{controller}';
						}
						// Заменяем макросы
						$annotation->name = static::macroSubstitute($annotation->name, $controllerClass);
						$annotation->description = static::macroSubstitute($annotation->description, $controllerClass);
					}
				}
			}
			
			// 4. Можно при желании обработать ещё title, description, summary и др.
			foreach (['summary','description','title','ref'] as $prop) {
				if (property_exists($annotation, $prop) && is_string($annotation->$prop)) {
					if ($ctx && $ctx->class) {
						$controllerClass = $ctx->class;
						$methodName = $ctx->method ?? null;
						$annotation->$prop = static::macroSubstitute($annotation->$prop, $controllerClass, $methodName);
					}
				}
			}
		}
	}
	
	/**
	 * Раскрывает макросы строке
	 * - {controller} -> Имя контроллера в строке URL (например, users)
	 * - {model} -> Имя модели, связанной с контроллером (например, Users)
	 * - {model->titles} -> Заголовок модели (например, "Пользователи")
	 * - {action} -> Имя действия в строке URL (например, view)
	 * @param string      $string
	 * @param string      $controller
	 * @param string|null $action
	 * @return string
	 */
	public static function macroSubstitute(string $string, string $controller, ?string $action=null): string
	{
		$controllerId = StringHelper::class2Id(StringHelper::removeSuffix($controller, 'Controller'));
		$modelClass='app\\models\\'.StringHelper::className(StringHelper::removeSuffix($controller, 'Controller'));
		$replacements=[
			'{controller}'=>$controllerId,
			'{model}'=>StringHelper::className($modelClass),
		];
		
		if (class_exists($modelClass)) {
			$replacements['{model->titles}']=$modelClass::$titles??$modelClass::$title??$modelClass;
		}
		
		if ($action) {
			$actionId = StringHelper::class2Id(StringHelper::removePrefix($action, 'action'));
			$replacements['{action}']=$actionId;
		}

		return str_replace(array_keys($replacements), array_values($replacements), $string);
	}
	
	/**
	 * Раскрывает макро-параметр {searchFields} -> параметры поиска по полям контроллера
	 * @param OA\Operation $operation
	 * @param              $analysis
	 * @return void
	 */
	private static function expandSearchFields(\OpenApi\Annotations\Operation $operation,$analysis): void
	{
		$ctx = $operation->_context;
		//если нет контекста или класса, то выходим
		if (!$ctx || !$ctx->class) return;

		$controllerClass = $ctx->class;
		//если такого контроллера нет, то выходим
		if (!class_exists($controllerClass)) return;

		//если параметров нет, то нечего делать
		if (!is_array($operation->parameters)) return;

		$expanded = [];
		foreach ($operation->parameters as $param) {
			if ($param instanceof OA\Parameter && $param->name === '{searchFields}') {
				if (property_exists($controllerClass, 'searchFields')) {
					// Получаем модель, связанную с контроллером
					$modelClass='app\\models\\'.StringHelper::className(StringHelper::removeSuffix($controllerClass, 'Controller'));
					if (class_exists($modelClass)) {
						$model = new $modelClass();
						foreach ($controllerClass::$searchFields as $name => $field) {
							$fieldName=is_numeric($name)?$field:$name;
							// Генерируем параметр поиска с типами из модели
							$searchParam = $model->generateSearchParameterAnnotation($fieldName, $param->_context);
							$expanded[] = $searchParam;
						}
					}
				}
				$analysis->annotations->detach($param);
			} elseif ($param instanceof OA\Parameter && $param->name === '{pagination}') {
				// Раскрываем макрос {pagination} в параметры per-page и page
				$limitParam = new OA\Parameter([
					'name' => 'per-page',
					'in' => 'query',
					'description' => 'Количество элементов на странице (0 - не делить на страницы)',
					'schema' => [
						'type' => 'integer',
						'minimum' => 1,
						'default' => 20,
						'example' => 1000,
					],
					'_context' => $param->_context,
				]);
				$offsetParam = new OA\Parameter([
					'name' => 'page',
					'in' => 'query',
					'description' => 'Номер страницы для вывода',
					'schema' => [
						'type' => 'integer',
						'minimum' => 0,
						'default' => 0,
						'example' => 2,
					],
					'_context' => $param->_context,
				]);
				$expanded[] = $limitParam;
				$expanded[] = $offsetParam;
				$analysis->annotations->detach($param);
			} elseif ($param instanceof OA\Parameter && $param->name === '{expand}') {
				// Раскрываем макрос {expand} в параметр expand
				$modelClass='app\\models\\'.StringHelper::className(StringHelper::removeSuffix($controllerClass, 'Controller'));
				$expandFields = [];
				if (class_exists($modelClass)) {
					$model = new $modelClass();
					$extraFields = $model->extraFields();
					if (!empty($extraFields)) {
						$expandFields = array_values($extraFields);
					}
				}
				if (!empty($expandFields)) {
				$description = 'Расширенные поля для включения в ответ (через запятую).';
					$description .= ' Доступные поля: ' . implode(', ', $expandFields);
					$expandParam = new OA\Parameter([
						'name' => 'expand',
						'in' => 'query',
						'description' => $description,
						'schema' => [
							'type' => 'string',
							'example' => !empty($expandFields) ? implode(',', array_slice($expandFields, 0, 2)) : 'field1,field2',
						],
						'_context' => $param->_context,
					]);
					$expanded[] = $expandParam;
				}
				$analysis->annotations->detach($param);
			} else {
				$expanded[] = $param;
			}
		}
		$operation->parameters = $expanded;
	}
}
