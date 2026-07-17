<?php

namespace tests\unit\models;

use app\models\base\ArmsModel;
use app\models\HistoryModel;
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
	 * is_inheritable — это наследование ВВЕРХ по дереву parentAttr: если своё
	 * значение пусто, движок (findRecursiveAttr / getAttributeInheritablePlaceholder /
	 * findRecursiveAttrNode / renderAttributeToText) читает $this->{parentAttr}.
	 * Пометить атрибут is_inheritable на модели, которая не умеет резолвить
	 * parentAttr — оксюморон: наследовать не от кого. Такая конфигурация
	 * НЕЖИЗНЕСПОСОБНА и валит форму на первом же пустом значении
	 * (UnknownPropertyException: <Model>::parent при валидации/тултипе).
	 *
	 * Если значение берётся не из дерева одноимённых предков, а из владельца
	 * или из нескольких своих полей — это не наследование, а вычисление
	 * «эффективного» значения: нотация *Effective (get<Attr>Effective), без
	 * is_inheritable и без суффикса Recursive.
	 */
	public function testInheritableAttrsRequireParentAttr(): void
	{
		$violations = [];
		foreach ($this->modelClasses() as $modelClass) {
			//*History-двойники переиспользуют attributeData/calc-trait живой модели
			//(со всеми флагами is_inheritable), но сами - read-only снапшоты без форм
			//и без ссылки-родителя (parentService/getParent живут на живой модели).
			//Наследование к ним неприменимо, инвариант проверяем на живых моделях.
			if (is_subclass_of($modelClass, HistoryModel::class)) continue;

			//часть моделей в unit-контексте без БД не конструируется/не отдаёт
			//метаданные - для них проверка неприменима, is_inheritable там нет
			try {
				$model = new $modelClass();
				$parentAttr = $model->parentAttr;
				$attributeData = $model->attributeData();
			} catch (\Throwable $e) {
				continue;
			}
			foreach ($attributeData as $attr => $data) {
				if (empty($data['is_inheritable'])) continue;
				if (!$model->canGetProperty($parentAttr)) {
					$violations[] = sprintf(
						"%s::%s помечен is_inheritable, но модель не резолвит parentAttr='%s' "
						."(нет геттера/связи к родителю) — наследовать не от кого",
						$modelClass, $attr, $parentAttr);
				}
			}
		}
		$this->assertSame([], $violations,
			"is_inheritable ТРЕБУЕТ наличие атрибута-родителя (parentAttr): без него наследование\n"
			."невозможно и форма падает UnknownPropertyException на первом пустом значении.\n"
			."Значение из владельца/своих полей, а не из дерева предков — это *Effective, не is_inheritable:\n  "
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
