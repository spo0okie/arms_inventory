<?php

namespace app\generation\generators;

use app\generation\AttributeContext;

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
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : '';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $minLength = $config['min_length'] ?? 5;
        $maxLength = $config['max_length'] ?? 20;
        $length = mt_rand($minLength, $maxLength);

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