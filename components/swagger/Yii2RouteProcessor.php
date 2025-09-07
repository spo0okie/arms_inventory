<?php
namespace app\components\swagger;

use app\helpers\StringHelper;
use OpenApi\Annotations as OA;
use OpenApi\Analysis;
use OpenApi\Generator;

class Yii2RouteProcessor
{
	public function __invoke(Analysis $analysis): void
	{
		foreach ($analysis->annotations as $annotation) {
			static::expandAnnotationMacros($annotation);
		}
	}
	
	public static function expandAnnotationMacros($annotation): void
	{
		$ctx = $annotation->_context??null; // контекст аннотации
		if ($annotation instanceof OA\Operation) {
			if ($ctx && $ctx->method && $ctx->class) {
				// Получаем имя контроллера и экшена
				$controllerClass = $ctx->class;
				$methodName = $ctx->method;
				
				// Только для контроллеров
				if (str_ends_with($controllerClass, 'Controller')) {
					
					// Если path пустой — проставляем
					if (empty($annotation->path)) {
						$annotation->path = '/{controller}/{action}';
					}
					
					if (empty($annotation->tags) || $annotation->tags === Generator::UNDEFINED) {
						$annotation->tags = ['{controller}'];
					}
					
					// Заменяем макросы
					$annotation->path = static::macroSubstitute($annotation->path, $controllerClass,$methodName);
					$tags=[];
					foreach ($annotation->tags as $tag)
						$tags[] = static::macroSubstitute($tag, $controllerClass,$methodName);
					$annotation->tags = $tags;
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
		
		// 3. Универсальная обработка свойств с ref
		if (property_exists($annotation, 'ref') && is_string($annotation->ref)) {
			if ($ctx && $ctx->class) {
				$controllerClass = $ctx->class;
				$methodName = $ctx->method ?? null;
				$annotation->ref = static::macroSubstitute($annotation->ref, $controllerClass, $methodName);
			}
		}
		
		// 4. Можно при желании обработать ещё title, description, summary и др.
		foreach (['summary','description','title'] as $prop) {
			if (property_exists($annotation, $prop) && is_string($annotation->$prop)) {
				if ($ctx && $ctx->class) {
					$controllerClass = $ctx->class;
					$methodName = $ctx->method ?? null;
					$annotation->$prop = static::macroSubstitute($annotation->$prop, $controllerClass, $methodName);
				}
			}
		}
	}
	public static function macroSubstitute(string $string, string $controller, ?string $action=null): string
	{
		$controllerId = StringHelper::class2Id(StringHelper::removeSuffix($controller, 'Controller'));
		$modelClass='app\\models\\'.StringHelper::className(StringHelper::removeSuffix($controller, 'Controller'));
		$replacements=[
			'{controller}'=>$controllerId,
			'{model}'=>StringHelper::className($modelClass),
		];
		
		if (class_exists($modelClass)) {
			//$model = new $modelClass();
			$replacements['{model->titles}']=$modelClass::$titles??$modelClass::$title??$modelClass;
		}
		
		if ($action) {
			$actionId = StringHelper::class2Id(StringHelper::removePrefix($action, 'action'));
			$replacements['{action}']=$actionId;
		}

		return str_replace(array_keys($replacements), array_values($replacements), $string);
	}
	
}
