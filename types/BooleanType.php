<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class BooleanType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'boolean';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeCheckbox($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		return Html::encode($value ? '1' : '0');
	}

	public function apiSchema(): array
	{
		return ['type' => 'boolean'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [0, 1];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : 0;
		}

		// Детерминированная генерация
		mt_srand($context->seed());

		$value=mt_rand(0, 1);
		
		mt_srand(); // сброс
		return $value;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('boolean')
		];
	}
}