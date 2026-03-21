<?php

namespace app\generation\generators;

/**
 * Генератор для типа string[] (массив строк)
 * Используется для API/генератора, не используется в UI
 */
class StringArrayGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return [];
        }

        //генерируем массив строк
        //по умолчанию 1-5 элементов
        $count = $params['count'] ?? random_int(1, 5);
        $min = $params['min'] ?? 3;
        $max = $params['max'] ?? 15;
        
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $length = random_int($min, $max);
            $result[] = $this->randomString($length);
        }
        
        return $result;
    }

    /**
     * Генерирует случайную строку
     * @param int $length длина строки
     * @return string
     */
    protected function randomString(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $result = '';
        $maxIndex = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
