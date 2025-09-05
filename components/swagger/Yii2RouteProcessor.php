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
		if ($annotation instanceof OA\Operation) {
			$ctx = $annotation->_context; // контекст аннотации
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
			$ctx = $annotation->_context; // контекст аннотации
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
	}
	public static function macroSubstitute(string $string, string $controller, ?string $action=null): string
	{
		$controllerId = StringHelper::class2Id(StringHelper::removeSuffix($controller, 'Controller'));
		$modelClass='app\\models\\'.StringHelper::className(StringHelper::removeSuffix($controller, 'Controller'));
		$replacements=[
			'{controller}'=>$controllerId,
			'{model}'=>$modelClass,
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
