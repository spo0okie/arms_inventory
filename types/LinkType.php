<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\web\View;

class LinkType extends BaseType
{
	public static function name(): string
	{
		return 'link';
	}

	public function searchHint(): ?string
	{
		return 'Ищется по имени связанного объекта.';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}

	/**
	 * Значение ссылки типом не рендерится: объекты выводятся только
	 * цепочкой renderItem()/ItemObjectWidget (правило unification.md «имя
	 * объекта — всегда renderItem»). Потребитель обязан увести ссылочный
	 * атрибут на объектный путь по метаданным, не доходя до этого вызова;
	 * попытка отрендерить значением — ошибка использования, роняем громко.
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		throw new \RuntimeException('Ссылочный атрибут '.get_class($model).'::'.$attribute
			.' рендерится объектным путём (renderItem), а не рендером типа');
	}

	public function apiSchema(): array
	{
		return ['type' => 'integer'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [];
	}

	public function generate(AttributeContext $context): mixed
	{
		$attr = $context->attribute;
		$isMany = \app\helpers\StringHelper::endsWith($attr, '_ids');

		if ($context->empty) {
			if ($context->isNullable()) {
				return null;
			}
			return $isMany ? [] : 1;
		}

		return $isMany ? [1] : 1;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('integer'),
		];
	}
}