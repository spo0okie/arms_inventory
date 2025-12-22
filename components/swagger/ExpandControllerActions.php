<?php
namespace app\components\swagger;

/**
 * Добавляет к описанию контроллера описания из его предка.
 * Раскрывает макросы (которые обязательно должны быть у предка, т.к. при наследовании многое должно из общего стать частным)
 */

use app\controllers\ArmsBaseController;
use app\helpers\StringHelper;
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
			if (!class_exists($fqcn)) continue;
			
			//пропускаем те что не контроллеры
			if (!is_subclass_of($fqcn, \yii\web\Controller::class)) continue;
			
			$methods = $definition['methods'];

			// ищем предков
			$ancestors = $analysis->getSuperClasses($fqcn);
			foreach ($ancestors as $ancestorFqcn => $ancestorDef) {
				$ancestorCtx = $ancestorDef['context'] ?? null;

				//пропускаем предков у которых нет аннотаций
				if (!$ancestorCtx || !is_iterable($ancestorCtx->annotations)) continue;
				
				/** @var ArmsBaseController $controller */
				$controller=new $fqcn(\Yii::$app->id, \Yii::$app);
				$disabledActions=$controller->disabledActions();
				
				//добавляем аннотации Тегов самого класса предка
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
					if (in_array($methodName, array_keys($methods))) {
						//continue;
						//этот метод переопределен в потомке
						$childMethodCtx = $methods[$methodName] ?? null;
						//если в потомке есть аннотации - пропускаем
						if ($childMethodCtx && is_iterable($childMethodCtx->annotations) && count($childMethodCtx->annotations)) {
							continue;
						}
					}
					
					//аннотаций нет
					if (!$methodCtx || !is_iterable($methodCtx->annotations)) {
						continue;
					}
					
					//определяем - экшен это или нет
					if (!str_starts_with($methodName,'action')) {
						continue;
					}
					
					$action=lcfirst(StringHelper::removePrefix($methodName,'action'));
					
					//если этот метод в этом потомке запрещен - пропускаем
					if (in_array($action,$disabledActions)) {
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
							$newCtx = clone $definition['context'];	// клонируем контекст из предка
							$newCtx->method = $methodName;			// указываем метод
							$newCtx->class = $fqcn;					// указываем класс
							$clone = $this->deepCloneAnnotation($ann,$newCtx); //клонируем аннотацию (со всеми вложенными)
							$analysis->addAnnotation($clone, $definition['context']); // добавляем в анализ
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Глубокое клонирование аннотации с заменой контекста
	 * @param         $ann
	 * @param Context $newCtx
	 * @return mixed
	 */
	public static function deepCloneAnnotation($ann, Context $newCtx): object
	{
		$clone = clone $ann;
		$clone->_context = $newCtx;
		
		// пробегаем по всем свойствам и если там массив/объекты аннотаций — тоже клонируем
		foreach (get_object_vars($clone) as $prop => $value) {
			if (is_array($value)) {
				$newArr = [];
				foreach ($value as $k => $v) {
					if ($v instanceof \OpenApi\Annotations\AbstractAnnotation) {
						$newArr[$k] = static::deepCloneAnnotation($v, $newCtx);
					} else {
						$newArr[$k] = is_object($v) ? clone $v : $v;
					}
				}
				$clone->$prop = $newArr;
			} elseif ($value instanceof \OpenApi\Annotations\AbstractAnnotation) {
				$clone->$prop = static::deepCloneAnnotation($value, $newCtx);
			} elseif (is_object($value)) {
				$clone->$prop = clone $value;
			}
		}
		
		return $clone;
	}
}
