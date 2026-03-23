<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор целых чисел
 */
class IntegerGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : 0;
        }

        $config = $context->generatorConfig();

        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 1000;

        // Детерминированная генерация на основе seed + имя атрибута
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

		$value=mt_rand($min, $max);

		mt_srand(); // сброс
        return $value;
    }
}
