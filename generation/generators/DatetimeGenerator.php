<?php

namespace app\generation\generators;

/**
 * Генератор для типа datetime (дата и время)
 */
class DatetimeGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return null;
        }

        //генерируем случайную дату и время
        //по умолчанию: от 2020-01-01 00:00:00 до текущей даты
        $minDate = $params['min'] ?? '2020-01-01 00:00:00';
        $maxDate = $params['max'] ?? date('Y-m-d H:i:s');

        $minTimestamp = strtotime($minDate);
        $maxTimestamp = strtotime($maxDate);
        
        $randomTimestamp = random_int($minTimestamp, $maxTimestamp);
        
        return date('Y-m-d H:i:s', $randomTimestamp);
    }
}
