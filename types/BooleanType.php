<?php

namespace app\types;

use app\generation\context\AttributeContext;

class BooleanType extends BaseType
{
	public static function name(): string
	{
		return 'boolean';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->checkbox();
	}

	public function apiSchema(): array
	{
		return ['type' => 'boolean'];
	}

	public function gridColumnClass(): ?string
	{
		return \kartik\grid\BooleanColumn::class;
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

		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		return $rng->getInt(0, 1);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('boolean')
		];
	}
}