<?php

namespace app\types;

use app\generation\context\AttributeContext;

class HwListType extends TextType
{
	public static function name(): string
	{
		return 'hw-list';
	}

	public function apiSchema(): array
	{
		return ['type' => 'string', 'format' => 'hw-list'];
	}

	public function samples(): array
	{
		return [
			'{"motherboard":{"manufacturer":"ASUSTeK COMPUTER INC.","product":"PRIME B560M-A","serial":"MB-0001"}},' . "\n" .
			'{"processor":{"model":"Intel(R) Core(TM) i7-10700","cores":"8","serial":"CPU-0001"}}',
		];
	}

	public function generate(AttributeContext $context): mixed
	{
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$strCtx=clone $context;
		$strCtx->min=10;
		$strCtx->max=20;
		$strType=new StringType();
		$intType=new IntegerType();

		$items = [
			['motherboard' => 	[
				'manufacturer' => $strType->generate($strCtx), 
				'product' => $strType->generate($strCtx), 
				'serial' => $strType->generate($strCtx)
			]],
			['memorybank' => 	[
				'manufacturer' => $strType->generate($strCtx), 		
				'capacity' => $intType->generate($strCtx), 
				'serial' => $strType->generate($strCtx)
			]],
			['processor' => 	[
				'model' => $strType->generate($strCtx), 	
				'cores' => $intType->generate($strCtx), 
				'serial' => $strType->generate($strCtx)
			]],
			['harddisk' => 		[
				'model' => $strType->generate($strCtx), 	
				'size' => $intType->generate($strCtx), 
				'serial' => $strType->generate($strCtx)
			]],
			['videocard' => 	[
				'name' => $strType->generate($strCtx), 
				'ram' => $intType->generate($strCtx), 
				'serial' => $strType->generate($strCtx)
			]],
		];

		return implode(",\n", array_map(
			static fn(array $item): string => (string)json_encode($item, JSON_UNESCAPED_UNICODE),
			$items
		));
	}

	public static function validateHwList(?string $value): ?string
	{
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}

		try {
			$items = json_decode('[' . $value . ']', true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return 'Ожидается набор JSON-объектов через запятую без внешнего массива';
		}

		if (!is_array($items) || $items === []) {
			return 'Список оборудования должен содержать хотя бы один объект';
		}

		$expected = [
			'motherboard' => ['manufacturer', 'product', 'serial'],
			'processor' => ['model', 'cores', 'serial'],
			'memorybank' => ['manufacturer', 'capacity', 'serial'],
			'harddisk' => ['model', 'size', 'serial'],
			'videocard' => ['name', 'ram', 'serial'],
		];

		foreach ($items as $item) {
			if (!is_array($item) || count($item) !== 1) {
				return 'Каждый объект оборудования должен содержать ровно один тип железа';
			}

			$type = array_key_first($item);
			if (!is_string($type) || !array_key_exists($type, $expected)) {
				return 'Допустимые типы: motherboard, processor, memorybank, harddisk, videocard';
			}

			$payload = $item[$type];
			if (!is_array($payload)) {
				return 'Описание типа железа должно быть JSON-объектом';
			}

			foreach ($expected[$type] as $field) {
				if (!array_key_exists($field, $payload) || !is_string($payload[$field])) {
					return 'Для ' . $type . ' поле ' . $field . ' обязательно и должно быть строкой';
				}
			}
		}

		return null;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition(function ($model, $attribute) {
				$error = static::validateHwList($model->$attribute);
				if ($error !== null) {
					$model->addError($attribute, $error);
				}
			}),
		];
	}
}
