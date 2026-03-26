<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор булевых значений
 */
class BooleanGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->empty) {
            return $context->isNullable() ? null : 0;
        }

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

		$value=mt_rand(0, 1);
		
		mt_srand(); // сброс
        return $value; 
    }
}
