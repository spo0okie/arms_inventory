<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\helpers\MacsHelper;

class MacsType extends TextType
{
	public static function name(): string
	{
		return 'macs';
	}

	public function samples(): array
	{
		return ['83aa792053a2','83aa791953a0'];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$min = $context->min ?? 18;
		$max = $context->max ?? 128;
		$count = $rng->getInt($min/18, $max/18);
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			// Создаём новый randomizer для каждого MAC с уникальным seed
			$macContext = new AttributeContext(
				attribute: $context->attribute . '_mac_' . $i,
				empty: $context->empty,
				model: $context->model,
				generationContext: $context->generationContext,
			);
			$macRng = $macContext->randomizer();
			
			$mac = sprintf(
				'%02X:%02X:%02X:%02X:%02X:%02X',
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255)
			);
			$result[] = $mac;
		}

		return implode("\n", $result);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition('filter',['filter'=>fn($v)=>MacsHelper::fixList($v)]),
		];
	}
}