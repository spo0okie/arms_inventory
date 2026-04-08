<?php

namespace app\types;

use app\generation\context\AttributeContext;


/**
 * VLAN ID который может быть integer от 1 и до 4096 или диапазон X-Y
 * При этом диапазон можно задать только при создании записи
 */
class VlanType extends IntegerType 
{
	public static function name(): string
	{
		return 'vlan';
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : 1;
		}

		$min = $context->min ?? 1;
		$max = $context->max ?? 4096;
		
		// Детерминированная генерация
		mt_srand($context->seed());

		$value=mt_rand($min, $max);

		mt_srand(); // сброс
		return $value;
	}



	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition(function($model,$attribute)
			{
				if (strpos($model->$attribute, '-') !== false) {
					if (!$model->isNewRecord) {
						$model->addError($attribute, 'Диапазон VLAN можно задавать только при создании.');
						return;
					}
					
					[$start, $end] = explode('-', $model->$attribute);
					$start = (int)$start;
					$end = (int)$end;
					
					if ($start < 1 || $end > 4096 || $start > $end) {
						$model->addError($attribute, 'Диапазон VLAN должен быть в пределах 1-4096 и корректным.');
					}
				} elseif ($model->$attribute < 1 || $model->$attribute > 4096) {
					$model->addError($attribute, 'VLAN должен быть в пределах 1-4096.');
				}
			}),
		];
	}
}