<?php

namespace app\generation\generators;

use app\generation\AttributeContext;

/**
 * Генератор дробных чисел
 */
class FloatGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : 0.0;
        }

        $config = $context->generatorConfig();

        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 1000;
        $decimals = $config['decimals'] ?? 2;

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $value = mt_rand($min * pow(10, $decimals), $max * pow(10, $decimals));

		mt_srand(); // сброс
        return $value / pow(10, $decimals);
    }
}