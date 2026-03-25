<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор MAC-адресов
 */
class MacsGenerator implements GeneratorInterface
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

		$min = $context->min ?? 1;
		$max = $context->max ?? 4;
        $count = mt_rand($min, $max);
        $result = [];
        
        for ($i = 0; $i < $count; $i++) {
            $mac = sprintf(
                '%02X:%02X:%02X:%02X:%02X:%02X',
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255)
            );
            $result[] = $mac;
        }

		mt_srand(); // сброс
		return implode("\n", $result);
    }
}
