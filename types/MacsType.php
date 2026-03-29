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

		// Детерминированная генерация
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		$min = $context->min ?? 18;
		$max = $context->max ?? 128;
		$count = mt_rand($min/18, $max/18);
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			$mac = sprintf(
				'%02X:%02X:%02X:%02X:%02X:%02X',
				mt_rand(0, 255),
				mt_rand(0, 255),
				mt_rand(0, 255),
				mt_rand(0, 255),
				mt_rand(0, 255),
				mt_rand(0, 255)
			);
			$result[] = $mac;
		}

		mt_srand(); // сброс
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