<?php

namespace app\types;

use app\generation\context\AttributeContext;

class FloatType extends BaseType
{
	public static function name(): string
	{
		return 'float';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
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
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$value = $rng->getInt($min * pow(10, $decimals), $max * pow(10, $decimals));
		return $value / pow(10, $decimals);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('number'),
		];
	}

}