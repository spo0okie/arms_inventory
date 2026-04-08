<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class JsonType extends TextType
{
	public static function name(): string
	{
		return 'json';
	}

	public function apiSchema(): array
	{
		return ['type' => 'string', 'format' => 'json'];
	}

	public function samples(): array
	{
		return ['{"key1": "value1", "key2": 2}'];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '{}';
		}

		$config = $context->generatorConfig();
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$keys = ['key1', 'key2', 'key3'];
		$result = [];
		
		foreach ($keys as $key) {
			$type = $config['key_types'][$key] ?? 'string';
			$result[$key] = $this->generateValue($type, $rng);
		}

		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Генерировать значение по типу
	 */
	private function generateValue(string $type, \Random\Randomizer $rng): mixed
	{
		return match ($type) {
			'string' => 'value_' . $rng->getInt(1, 100),
			'integer' => $rng->getInt(1, 1000),
			'float' => $rng->getInt(1, 1000) / 10,
			'boolean' => (bool) $rng->getInt(0, 1),
			'array' => [$rng->getInt(1, 10), $rng->getInt(1, 10)],
			default => 'value',
		};
	}
}