<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор дат и времени
 */
class DatetimeGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : date('Y-m-d H:i:s');
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $minYear = $config['min_year'] ?? 2020;
        $maxYear = $config['max_year'] ?? date('Y');
        
        $year = mt_rand($minYear, $maxYear);
        $month = mt_rand(1, 12);
        $day = mt_rand(1, 28);
        $hour = mt_rand(0, 23);
        $minute = mt_rand(0, 59);
        $second = mt_rand(0, 59);
        
		mt_srand(); // сброс
        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }
}
