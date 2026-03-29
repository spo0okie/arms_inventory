<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use app\models\NetIps;
use yii\helpers\Html;
use yii\web\View;

class IpsType implements AttributeTypeInterface
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

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
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
		// Детерминированная генерация
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		if ($context->model instanceof \app\models\Networks && $context->attribute === 'text_addr') {
			$octet = 1 + ($seed % 200);
			return '198.18.' . $octet . '.0/24';
		}
		if ($context->model instanceof \app\models\NetIps && $context->attribute === 'text_addr') {
			$octet = 1 + ($seed % 200);
			return '203.0.113.' . $octet;
		}

		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$config = $context->generatorConfig();


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

	private function generatePrivateIP(): string
	{
		$ranges = [
			[10, 10],           // 10.0.0.0/8
			[172, 16, 31],     // 172.16.0.0/12
			[192, 168],        // 192.168.0.0/16
		];
		
		$selected = $ranges[mt_rand(0, count($ranges) - 1)];
		
		if (count($selected) === 2) {
			return sprintf('%d.%d.%d.%d', 
				$selected[0], 
				$selected[1], 
				mt_rand(1, 254), 
				mt_rand(1, 254)
			);
		} else {
			return sprintf('%d.%d.%d.%d', 
				$selected[0], 
				$selected[1], 
				$selected[2], 
				mt_rand(1, 254)
			);
		}
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition(fn($model,$attribute)=>NetIps::validateInput($model,$attribute)),
			new RuleDefinition('filter', ['filter' => fn ($v) => NetIps::filterInput($v)]),
		];
	}	
}