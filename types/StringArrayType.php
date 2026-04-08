<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class StringArrayType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'string[]';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextarea($model, $attribute, $inputOptions);
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
		
		// Детерминированная генерация
		mt_srand($context->seed());

		$min = $context->min ?? 2;
		$max = $context->max ?? 8;
		$itemCount = mt_rand($min, $max);

		$result = [];
		for ($i = 0; $i < $itemCount; $i++) {
			$minLen = $config['min_length'] ?? 10;
			$maxLen = $config['max_length'] ?? 20;
			$len = mt_rand($minLen, $maxLen);
			
			$chars = 'abcdefghijklmnopqrstuvwxyz';
			$str = '';
			for ($j = 0; $j < $len; $j++) {
				$str .= $chars[mt_rand(0, strlen($chars) - 1)];
			}
			$result[] = $str;
		}

		mt_srand(); // сброс
		return $result;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('each',['rule'=>'string']),
		];
	}
}