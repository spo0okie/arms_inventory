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
		
		// Детерминированная генерация
		mt_srand($context->seed());

		$keys = ['key1', 'key2', 'key3'];
		$result = [];
		
		foreach ($keys as $key) {
			$type = $config['key_types'][$key] ?? 'string';
			$result[$key] = $this->generateValue($type);
		}

		mt_srand(); // сброс
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Генерировать значение по типу
	 */
	private function generateValue(string $type): mixed
	{
		return match ($type) {
			'string' => 'value_' . mt_rand(1, 100),
			'integer' => mt_rand(1, 1000),
			'float' => mt_rand(1, 1000) / 10,
			'boolean' => (bool) mt_rand(0, 1),
			'array' => [mt_rand(1, 10), mt_rand(1, 10)],
			default => 'value',
		};
	}
}