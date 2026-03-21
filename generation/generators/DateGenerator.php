<?php

namespace app\generation\generators;

/**
 * Генератор для типа date (дата)
 */
class DateGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return null;
        }

        //генерируем случайную дату
        //по умолчанию: от 2020-01-01 до текущей даты
        $minDate = $params['min'] ?? '2020-01-01';
        $maxDate = $params['max'] ?? date('Y-m-d');

        $minTimestamp = strtotime($minDate);
        $maxTimestamp = strtotime($maxDate);
        
        $randomTimestamp = random_int($minTimestamp, $maxTimestamp);
        
        return date('Y-m-d', $randomTimestamp);
    }
}
