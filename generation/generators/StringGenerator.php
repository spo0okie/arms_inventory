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
        if ($context->model instanceof \app\modules\schedules\models\SchedulesEntries && $context->attribute === 'schedule') {
            return '00:00-23:59';
        }

        if ($context->model instanceof \app\models\Techs && $context->attribute === 'num') {
            $seed = $context->generationContext->seed + crc32($context->attribute);
            $prefix = 'T' . ($seed % 1000);
            return \app\models\Techs::fetchNextNum($prefix);
        }

        if (str_contains($context->attribute, 'color') && !$context->isNullable()) {
            return $this->generateHexColor($context);
        }

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

    private function generateHexColor(AttributeContext $context): string
    {
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);
        $color = sprintf('#%02X%02X%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        mt_srand();
        return $color;
    }
}