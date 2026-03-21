<?php

namespace app\generation\generators;

/**
 * Генератор для типа radios (переключатели, 2+ значений)
 * Использует fieldList из параметров для получения значений
 */
class RadiosGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return null;
        }

        //получаем список возможных значений из fieldList
        $fieldList = $params['fieldList'] ?? null;
        
        //если fieldList не передан, возвращаем null (т.к. нет валидных значений)
        if (empty($fieldList) || !is_array($fieldList)) {
            return null;
        }

        //выбираем случайный индекс из fieldList
        $keys = array_keys($fieldList);
        $randomKey = $keys[random_int(0, count($keys) - 1)];
        
        return $randomKey;
    }
}
