<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class FloatType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'float';
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
		return ['type' => 'number'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [1.5];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : 0.0;
		}

		$config = $context->generatorConfig();

		$min = $context->min ?? 0;
		$max = $context->max ?? 10000;
		$decimals = 2;

		// Детерминированная генерация
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		$value = mt_rand($min * pow(10, $decimals), $max * pow(10, $decimals));

		mt_srand(); // сброс
		return $value / pow(10, $decimals);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('number'),
		];
	}

}