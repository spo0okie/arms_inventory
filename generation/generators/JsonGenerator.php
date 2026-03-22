<?php

namespace app\generation\generators;

/**
 * Генератор для типа json (JSON-строка)
 * 
 * Генерирует случайный JSON-объект (ассоциативный массив)
 */
class JsonGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '{}';
        }

        //получаем количество полей в объекте
        $minFields = $params['minFields'] ?? 1;
        $maxFields = $params['maxFields'] ?? 5;
        $fieldCount = random_int($minFields, $maxFields);

        //генерируем случайный ассоциативный массив
        $data = self::generateRandomObject($fieldCount);

        //кодируем в JSON строку
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Генерация случайного объекта (ассоциативного массива)
     */
    private static function generateRandomObject(int $fieldCount): array
    {
        $result = [];
        $fieldNames = ['name', 'value', 'id', 'type', 'status', 'data', 'config', 'options', 'meta', 'settings'];
        
        for ($i = 0; $i < $fieldCount; $i++) {
            //выбираем имя поля
            $fieldName = $fieldNames[random_int(0, count($fieldNames) - 1)] . '_' . random_int(1, 100);
            
            //генерируем случайное значение (различные типы)
            $valueType = random_int(0, 3);
            
            switch ($valueType) {
                case 0: //строка
                    $result[$fieldName] = StringGenerator::randomString(random_int(5, 20));
                    break;
                case 1: //число
                    $result[$fieldName] = random_int(1, 1000);
                    break;
                case 2: //булево
                    $result[$fieldName] = random_int(0, 1) === 1;
                    break;
                case 3: //массив
                    $result[$fieldName] = [random_int(1, 10), random_int(1, 10), random_int(1, 10)];
                    break;
            }
        }

        return $result;
    }

}
