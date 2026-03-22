<?php

namespace app\generation\generators;

/**
 * Генератор для типа boolean (чекбокс да/нет)
 */
class BooleanGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return false;
        }

        //генерируем случайное булево значение
        return random_int(0, 1) === 1;
    }
}
