<?php

namespace app\generation\generators;

/**
 * Генератор для типа toggle (boolean с кастомными названиями для 0 и 1)
 * Использует fieldList из параметров для получения значений
 */
class ToggleGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return 0;
        }

        //по умолчанию toggle хранит 0 или 1
        return random_int(0, 1);
    }
}
