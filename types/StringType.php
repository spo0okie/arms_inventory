<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class StringType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'string';
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
		return ['type' => 'string'];
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
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$min = $context->min ?? 5;
		$max = $context->max ?? 20;
		$length = $rng->getInt($min, $max);

		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$result = '';
		$charsLength = strlen($chars);

		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[$rng->getInt(0, $charsLength - 1)];
		}
		
		return $result;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
		];
	}
}
