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
		
		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        //получаем количество полей в объекте
        $min = $params['min'] ?? 1;
        $max = $params['max'] ?? 5;
        $count = mt_rand($min, $max);

        //генерируем случайный ассоциативный массив
        $data = self::generateRandomObject($count);

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
            $fieldName = $fieldNames[mt_rand(0, count($fieldNames) - 1)] . '_' . mt_rand(1, 100);
            
            //генерируем случайное значение (различные типы)
            $valueType = mt_rand(0, 3);
            
            switch ($valueType) {
                case 0: //строка
                    $result[$fieldName] = StringGenerator::randomString(mt_rand(5, 20));
                    break;
                case 1: //число
                    $result[$fieldName] = mt_rand(1, 1000);
                    break;
                case 2: //булево
                    $result[$fieldName] = mt_rand(0, 1) === 1;
                    break;
                case 3: //массив
                    $result[$fieldName] = [mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10)];
                    break;
            }
        }

        return $result;
    }

}
