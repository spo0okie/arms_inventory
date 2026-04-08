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

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextarea($model, $attribute, $inputOptions);
	}
	
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация
		mt_srand($context->seed());
		
		$min = $context->min ?? 16;
		$max = $context->max ?? 128;
		$count = mt_rand($min/16, $max/16);
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			$result[] = $this->generatePrivateIP();
		}

		mt_srand(); // сброс
		return implode("\n", $result);
	}
}