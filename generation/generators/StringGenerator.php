<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор строк
 */
class StringGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->empty) {
            return $context->isNullable() ? null : '';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

		$min = $context->min ?? 5;
		$max = $context->max ?? 20;
        $length = mt_rand($min, $max);

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        $charsLength = strlen($chars);

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

		mt_srand(); // сброс
        return $result;
    }
}