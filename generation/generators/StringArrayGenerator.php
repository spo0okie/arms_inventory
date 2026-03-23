<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор массивов строк (string[])
 */
class StringArrayGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : [];
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $minItems = $config['min_items'] ?? 1;
        $maxItems = $config['max_items'] ?? 5;
        $itemCount = mt_rand($minItems, $maxItems);

        $result = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $minLen = $config['min_length'] ?? 3;
            $maxLen = $config['max_length'] ?? 10;
            $len = mt_rand($minLen, $maxLen);
            
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            $str = '';
            for ($j = 0; $j < $len; $j++) {
                $str .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $result[] = $str;
        }

		mt_srand(); // сброс
        return $result;
    }
}
