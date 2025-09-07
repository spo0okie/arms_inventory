<?php
namespace app\components\swagger;

//добавляет к описанию контроллера описания из его предка

use app\helpers\StringHelper;
use OpenApi\Analysis;
use OpenApi\Annotations\OpenApi;
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
				
				$controller=new $fqcn(\Yii::$app->id, \Yii::$app);
				$disabledActions=$controller->disabledActions();
				//добавляем аннотации самого класса предка
				foreach ($ancestorCtx->annotations as $ann) {
					if (
						$ann instanceof \OpenApi\Annotations\Tag
					) {
						foreach ($definition['context']->annotations as $current) {
							if ($current instanceof \OpenApi\Annotations\Tag &&
								$current->name === $ann->name) {
								//такой тег уже есть - пропускаем
								continue 2;
							}
						}
						$clone = $this->deepCloneAnnotation($ann,$definition['context']);
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
					
					if (str_starts_with($methodName,'action')) {
						$action=lcfirst(StringHelper::removePrefix($methodName,'action'));
						if (in_array($action,$disabledActions)) {
							continue;
						}
					} else {
						//не экшен - пропускаем
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
							/** @var Context $newCtx */
							$newCtx = clone $definition['context'];
							$newCtx->method = $methodName;
							$newCtx->class = $fqcn;
							$clone = $this->deepCloneAnnotation($ann,$newCtx);
							//$clone->_context=$newCtx;
							$analysis->addAnnotation($clone, $definition['context']);
						}
					}
				}
			}
		}
		
		foreach ($analysis->getAnnotationsOfType(\OpenApi\Annotations\Operation::class) as $operation) {
			$this->expandSearchFields($operation,$analysis);
		}
	}
	
	
	private function deepCloneAnnotation($ann, Context $newCtx): object
	{
		$clone = clone $ann;
		$clone->_context = $newCtx;
		
		// пробегаем по всем свойствам и если там массив/объекты аннотаций — тоже клонируем
		foreach (get_object_vars($clone) as $prop => $value) {
			if (is_array($value)) {
				$newArr = [];
				foreach ($value as $k => $v) {
					if (is_object($v) && $v instanceof \OpenApi\Annotations\AbstractAnnotation) {
						$newArr[$k] = $this->deepCloneAnnotation($v, $newCtx);
					} else {
						$newArr[$k] = is_object($v) ? clone $v : $v;
					}
				}
				$clone->$prop = $newArr;
			} elseif (is_object($value) && $value instanceof \OpenApi\Annotations\AbstractAnnotation) {
				$clone->$prop = $this->deepCloneAnnotation($value, $newCtx);
			} elseif (is_object($value)) {
				$clone->$prop = clone $value;
			}
		}
		
		return $clone;
	}
	
	private function expandSearchFields(\OpenApi\Annotations\Operation $operation,$analysis): void
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
				//$param->_unmerged = ['removed'];; // подсказка анализатору "не включать"
				// если у контроллера определены поля
				if (property_exists($controllerClass, 'searchFields')) {
					foreach ($controllerClass::$searchFields as $name => $field) {
						$fieldName=is_numeric($name)?$field:$name;
						$clone=$this->deepCloneAnnotation($param,$param->_context);
						$clone->name=$fieldName;
						$clone->description="Фильтр по полю {$fieldName}";
						//$clone->schema=new OA\Schema(type: "string");
						$expanded[]=$clone;
					}
				}
				$analysis->annotations->detach($param);
			} else {
				$expanded[] = $param;
			}
		}
		$operation->parameters = $expanded;
	}
}
