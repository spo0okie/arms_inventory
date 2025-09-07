<?php

namespace app\components\swagger;

use app\helpers\StringHelper;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Context;

class ExpandSearchFields
{
	public function __invoke(Analysis $analysis): void
	{
		// Берём все операции (Get/Post/Put/etc)
		foreach ($analysis->getAnnotationsOfType(OA\Operation::class) as $operation) {
			$this->expandSearchFields($operation);
		}
		
		/*foreach ($analysis->classes as $fqcn => $definition) {
			// возможно namespace mismatch
			if (!class_exists($fqcn)) {
				continue;
			}
			
			//пропускаем те что не контроллеры
			if (!is_subclass_of($fqcn, \yii\web\Controller::class)) {
				continue;
			}
			
			
			// ищем предков
			$methods = $definition['methods'];
		
				
			foreach ($methods as $methodName => $methodCtx) {
				if (!$methodCtx || !is_iterable($methodCtx->annotations)) {
					continue;
				}
				
				//добавляем аннотации методов предка
				foreach ($methodCtx->annotations as $ann) {
					if ($ann instanceof OA\Operation) {
						$this->expandSearchFields($ann);
					}
				}
			}
		}*/
	}
	
	private function expandSearchFields(OA\Operation $operation): void
	{
		$ctx = $operation->_context;
		if (!$ctx || !$ctx->class) {
			return;
		}
		
		$controllerClass = $ctx->class;
		if (!class_exists($controllerClass)) {
			return;
		}
		
		if (!is_array($operation->parameters)) {
			return;
		}
		
		$expanded = [];
		foreach ($operation->parameters as $param) {
			if ($param instanceof OA\Parameter && $param->name === '{searchFields}') {
				// если у контроллера определены поля
				if (property_exists($controllerClass, 'searchFields')) {
					foreach ($controllerClass::$searchFields as $name => $field) {
						$expanded[] = new OA\Parameter([
							'name'        => $name,
							'in'          => 'query',
							'required'    => false,
							'description' => is_string($field) ? "Фильтр по полю {$field}" : "Фильтр по {$name}",
							'schema'      => new OA\Schema([
								'type' => 'string',
							]),
							'_context'    => $param->_context,
						]);
					}
				}
			} else {
				$expanded[] = $param;
			}
		}
		$operation->parameters = $expanded;
	}
}