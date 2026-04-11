<?php

namespace app\types;

use app\generation\context\AttributeContext;

class SwListType extends TextType
{
	public static function name(): string
	{
		return 'soft-list';
	}

	public function apiSchema(): array
	{
		return ['type' => 'string', 'format' => 'soft-list'];
	}

	public function samples(): array
	{
		return [
			'{"publisher":"Microsoft","name":"Windows 10 Pro"},' . "\n" .
			'{"publisher":"Google LLC","name":"Google Chrome"}',
		];
	}

	public function generate(AttributeContext $context): mixed
	{
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$rng = $context->randomizer();
		$publishers = ['Microsoft', 'Google LLC', 'VideoLAN', 'Mozilla', 'JetBrains'];
		$products = ['Windows 11 Pro', 'Google Chrome', 'VLC media player', 'Firefox', 'PhpStorm'];
		$count = $rng->getInt(5, 10);
		$items = [];
		for ($i = 0; $i < $count; $i++) {
			$items[] = json_encode([
				'publisher' => AttributeContext::pickRandomValue($publishers, $rng),
				'name' => AttributeContext::pickRandomValue($products, $rng),
			], JSON_UNESCAPED_UNICODE);
		}

		return implode(",\n", $items);
	}

	public static function validateSoftList(?string $value): ?string
	{
		$value = trim((string)$value);
		if ($value === '') {
			return null;
		}

		try {
			$items = json_decode('[' . $value . ']', true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return 'Ожидается список JSON-объектов через запятую без внешнего массива';
		}

		if (!is_array($items) || $items === []) {
			return 'Список ПО должен содержать хотя бы один объект';
		}

		foreach ($items as $item) {
			if (!is_array($item)) {
				return 'Каждый элемент списка ПО должен быть JSON-объектом';
			}
			if (!array_key_exists('publisher', $item) || !is_string($item['publisher'])) {
				return 'У элемента ПО поле publisher обязательно и должно быть строкой';
			}
			if (!array_key_exists('name', $item) || !is_string($item['name'])) {
				return 'У элемента ПО поле name обязательно и должно быть строкой';
			}
		}

		return null;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition(function ($model, $attribute) {
				$error = static::validateSoftList($model->$attribute);
				if ($error !== null) {
					$model->addError($attribute, $error);
				}
			}),
		];
	}
}
