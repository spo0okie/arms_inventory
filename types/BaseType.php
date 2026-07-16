<?php

namespace app\types;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Базовый класс типов атрибутов — место дефолтных реализаций опциональных
 * возможностей типа. Обязательный контракт описан в AttributeTypeInterface;
 * все типы наследуются от этого класса (напрямую или через другой тип).
 */
abstract class BaseType implements AttributeTypeInterface
{
	/**
	 * Типовой рендер значения атрибута в местах вывода (контракт —
	 * см. AttributeTypeInterface::renderOutput). Дефолт — простейший
	 * рендер, общий для большинства типов: экранированное значение как
	 * есть, массив значений — списком готовых элементов. Подача
	 * (карточка, заголовок, разделители, объекты-ссылки) — забота
	 * потребителя, а не типа. Тип переопределяет метод, когда у него
	 * есть собственная подача значения (обогащение — точечно по ходу
	 * аудита карточек, plans/view-hints.md 4в).
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		return $this->renderPlainValue($model,$attribute);
	}

	/**
	 * Простейший рендер значения (экранированный текст, массив —
	 * список элементов). Вынесен отдельно, чтобы потомки могли отступить
	 * от рендера своего родителя обратно к стандартному (например,
	 * наследники TextType до собственного обогащения).
	 */
	protected function renderPlainValue(ArmsModel $model, string $attribute): mixed
	{
		$value=ArrayHelper::getValue($model,$attribute);
		if (is_array($value))
			return array_map(static fn($item)=>Html::encode((string)$item),$value);
		return Html::encode((string)$value);
	}

	/**
	 * Diff двух значений атрибута для карточки изменений журнала истории
	 * (контракт — см. AttributeTypeInterface::diffValues). Дефолт — null:
	 * собственного diff-представления нет, потребитель показывает генерик
	 * («старое → новое» или множества).
	 */
	public function diffValues(?string $old, ?string $new): ?array {return null;}

	/**
	 * Типовая часть подсказки заполнения — «как вводить» (лёгкий слой
	 * документации, см. ui-sources.md §0.1). Сборщик тултипов подклеивает
	 * её к смысловой части атрибута в форме. null — типовой части нет.
	 */
	public function inputHint(): ?string {return null;}

	/**
	 * Типовая часть подсказки поиска — «как искать»: только отличия от
	 * общего синтаксиса поиска. Подклеивается в тултип колонки с фильтром.
	 * null — типовой части нет.
	 */
	public function searchHint(): ?string {return null;}
}
