<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class IntegerType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'integer';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
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
		return [1];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : 0;
		}

		$min = $context->min ?? 0;
		$max = $context->max ?? 1000;

		// Детерминированная генерация на основе seed + имя атрибута
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		$value=mt_rand($min, $max);

		mt_srand(); // сброс
		return $value;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('integer'),
		];
	}
}