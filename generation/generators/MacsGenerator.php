<?php

namespace app\generation\generators;

use app\generation\AttributeContext;

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
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : '';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $count = $config['count'] ?? 1;
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
