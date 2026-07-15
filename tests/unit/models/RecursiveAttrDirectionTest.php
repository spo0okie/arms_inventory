<?php

namespace tests\unit\models;

use app\models\base\ArmsModel;
use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionMethod;

/**
 * Сторож направления рекурсии *Recursive-атрибутов.
 *
 * Конвенция (докблок AttributeDataModelTrait, ArmsModel::__get): суффикс
 * <attr>Recursive по умолчанию означает наследование ВВЕРХ от родителей
 * (is_inheritable) — так его разрешает магический __get и так его
 * аннотирует тултип (AttributeTooltip, блок 1б «источник значения»).
 *
 * Но часть явных геттеров get<Attr>Recursive() собирает значение ВНИЗ
 * с потомков (childrenRecursive, phonesRecursive и т.п.). Чтобы конвенция
 * не врала, такой атрибут ОБЯЗАН объявить в attributeData собственную
 * запись с 'is_collectable'=>true — иначе UI пометит его «наследуемым»
 * и поведет интроспекцию цепочки не в ту сторону.
 *
 * Тест требует: у каждого явного get<Attr>Recursive() без обязательных
 * параметров направление объявлено ровно одно — is_inheritable (вверх,
 * флаг на базовом атрибуте по канону) ЛИБО is_collectable (вниз, флаг
 * на самом Recursive-атрибуте).
 */
class RecursiveAttrDirectionTest extends Unit
{
	public function testExplicitRecursiveGettersDeclareDirection(): void
	{
		$violations = [];
		foreach ($this->modelClasses() as $modelClass) {
			$ref = new ReflectionClass($modelClass);
			$model = null;
			foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				//только явные геттеры вида getXxxRecursive() без обязательных
				//аргументов (с аргументами - не атрибуты, магия их не разрешает)
				if (!preg_match('/^get([A-Z]\w*)Recursive$/', $method->getName(), $m)) continue;
				if ($method->getNumberOfRequiredParameters() > 0) continue;

				$attr = lcfirst($m[1]).'Recursive';
				$model = $model ?? new $modelClass();
				try {
					$inheritable = (bool)$model->attributeIsInheritable($attr);
					$collectable = (bool)$model->attributeIsCollectable($attr);
				} catch (\Throwable $e) {
					$violations[] = sprintf('%s::%s (%s): метаданные не разрешились - %s',
						$modelClass, $attr, $method->getDeclaringClass()->getShortName(), $e->getMessage());
					continue;
				}
				if ($inheritable && $collectable) {
					$violations[] = sprintf('%s::%s: объявлены ОБА направления (is_inheritable и is_collectable) - выбери одно',
						$modelClass, $attr);
				} elseif (!$inheritable && !$collectable) {
					$violations[] = sprintf('%s::%s (геттер в %s): направление рекурсии не объявлено',
						$modelClass, $attr, $method->getDeclaringClass()->getShortName());
				}
			}
		}
		$this->assertSame([], $violations,
			"У этих *Recursive-атрибутов не объявлено направление рекурсии.\n"
			."Обход к РОДИТЕЛЯМ (наследование) - 'is_inheritable'=>true на базовом атрибуте;\n"
			."обход к ПОТОМКАМ (сбор значений) - 'is_collectable'=>true на самом Recursive-атрибуте\n"
			."(отдельная запись attributeData со своими label/hint). См. докблок AttributeDataModelTrait:\n  "
			.implode("\n  ", $violations));
	}

	/**
	 * Все конкретные модели-наследники ArmsModel (models/ и modules/x/models/).
	 * @return string[]
	 */
	protected function modelClasses(): array
	{
		$root = str_replace('\\', '/', rtrim(codecept_root_dir(), '/\\'));
		$files = array_merge(
			glob($root.'/models/*.php') ?: [],
			glob($root.'/modules/*/models/*.php') ?: []
		);
		$result = [];
		foreach ($files as $file) {
			$rel = substr(str_replace('\\', '/', $file), strlen($root) + 1);
			$fqcn = 'app\\'.str_replace('/', '\\', substr($rel, 0, -4));
			if (!class_exists($fqcn)) continue;
			$ref = new ReflectionClass($fqcn);
			if ($ref->isAbstract() || !$ref->isSubclassOf(ArmsModel::class)) continue;
			$result[] = $fqcn;
		}
		return $result;
	}
}
