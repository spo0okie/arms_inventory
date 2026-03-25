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
        if ($context->empty) {
            return $context->isNullable() ? null : '';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

		$min = $context->min ?? 20;
		$max = $context->max ?? 100;
        $length = mt_rand($min, $max);

        $words = ['Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 
                  'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
                  'magna', 'aliqua', 'Ut', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
                  'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo'];
        
        $result = '';        
        $enough = false;
        while (!$enough) {
            if ($result) {
                $result .= ' ';
            }
			$word=$words[mt_rand(0, count($words) - 1)];
			if (strlen($result.$word) > $length) {
				$enough=true;
			} else {
				$result.=$word;
			}
        }

        return $result;
    }
}
