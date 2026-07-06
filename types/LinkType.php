<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\helpers\Html;
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

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if (is_array($value)) {
			$value = implode(', ', $value);
		}
		return Html::encode((string)$value);
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