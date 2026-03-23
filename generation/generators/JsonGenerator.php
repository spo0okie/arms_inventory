<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор JSON данных
 */
class JsonGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : '{}';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $keys = $config['keys'] ?? ['key1', 'key2', 'key3'];
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