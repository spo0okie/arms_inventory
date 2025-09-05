<?php
namespace app\components\swagger;

//добавляет к описанию контроллера описания из его предка

use OpenApi\Analysis;
use OpenApi\Attributes as OA;
use OpenApi\Context;


class ExpandControllerActions
{
	public function __invoke(Analysis $analysis)
	{
		//перебираем обнаруженные классы
		foreach ($analysis->classes as $fqcn => $definition) {
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
			$ancestors = $analysis->getSuperClasses($fqcn);
			foreach ($ancestors as $ancestorFqcn => $ancestorDef) {
				$ancestorCtx = $ancestorDef['context'] ?? null;
				if (!$ancestorCtx || !is_iterable($ancestorCtx->annotations)) {
					continue;
				}
				
				//добавляем аннотации самого класса предка
				foreach ($ancestorCtx->annotations as $ann) {
					if (
						$ann instanceof \OpenApi\Annotations\Tag
					) {
						$clone = clone $ann;
						$clone->_context=$definition['context'];
						$analysis->addAnnotation($clone, $definition['context']);
						$analysis->openapi->merge([$clone],true);
					}
				}
				
				//просматриваем методы предка
				$ancestorMethods = $ancestorDef['methods'] ?? [];
				if (empty($ancestorMethods)) continue; //если нечего смотреть - пропускаем
				
				foreach ($ancestorMethods as $methodName => $methodCtx) {
					//$methodCtx = $methodDef['context'] ?? null;
					if (in_array($methodName, $methods)) {
						//этот метод переопределен в потомке - пропускаем
						continue;
					}
					if (!$methodCtx || !is_iterable($methodCtx->annotations)) {
						continue;
					}
					
					//добавляем аннотации методов предка
					foreach ($methodCtx->annotations as $ann) {
						if ($ann instanceof OA\Get ||
							$ann instanceof OA\Post ||
							$ann instanceof OA\Put ||
							$ann instanceof OA\Delete ||
							$ann instanceof OA\Patch) {
							// переносим аннотацию в потомка
							$clone = clone $ann;
							/** @var Context $newCtx */
							$newCtx = clone $definition['context'];
							$newCtx->method = $methodName;
							$newCtx->class = $fqcn;
							$clone->_context=$newCtx;
							$analysis->addAnnotation($clone, $definition['context']);
						}
					}
				}
			}
		}
	}
	
	
	
	
}
