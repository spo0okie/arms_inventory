<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор текстовых полей (text)
 */
class TextGenerator implements GeneratorInterface
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

        $minLength = $config['min_length'] ?? 20;
        $maxLength = $config['max_length'] ?? 200;
        $length = mt_rand($minLength, $maxLength);

        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 
                  'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
                  'magna', 'aliqua', 'Ut', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
                  'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo'];
        
        $result = '';
        $wordCount = (int) ($length / 5);
        
        for ($i = 0; $i < $wordCount; $i++) {
            if ($i > 0) {
                $result .= ' ';
            }
            $result .= $words[mt_rand(0, count($words) - 1)];
        }

        return $result;
    }
}
