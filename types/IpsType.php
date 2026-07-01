<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use app\models\NetIps;
use yii\helpers\Html;
use yii\web\View;

class IpsType extends IpType
{
	public static function name(): string
	{
		return 'ips';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}
	
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();
		
		$min = $context->min ?? 16;
		$max = $context->max ?? 128;
		$count = $rng->getInt($min/16, $max/16);
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			// Создаём новый randomizer для каждого IP с уникальным seed
			$ipContext = new AttributeContext(
				attribute: $context->attribute . '_ip_' . $i,
				empty: $context->empty,
				model: $context->model,
				generationContext: $context->generationContext,
			);
			$result[] = $this->generatePrivateIP($ipContext->randomizer());
		}

		return implode("\n", $result);
	}
}