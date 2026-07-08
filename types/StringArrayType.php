<?php

namespace app\types;

use app\generation\context\AttributeContext;

class StringArrayType extends BaseType
{
	public static function name(): string
	{
		return 'string[]';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}

	public function apiSchema(): array
	{
		return ['type' => 'array', 'items' => ['type' => 'string']];
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
			return $context->isNullable() ? null : [];
		}

		$config = $context->generatorConfig();
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$min = $context->min ?? 2;
		$max = $context->max ?? 8;
		$itemCount = $rng->getInt($min, $max);

		$result = [];
		for ($i = 0; $i < $itemCount; $i++) {
			$minLen = $config['min_length'] ?? 10;
			$maxLen = $config['max_length'] ?? 20;
			$len = $rng->getInt($minLen, $maxLen);
			
			$chars = 'abcdefghijklmnopqrstuvwxyz';
			$str = '';
			for ($j = 0; $j < $len; $j++) {
				$str .= $chars[$rng->getInt(0, strlen($chars) - 1)];
			}
			$result[] = $str;
		}

		return $result;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('each',['rule'=>'string']),
		];
	}
}