<?php

namespace app\generation\generators;

/**
 * Генератор для типа macs (список MAC адресов)
 */
class MacsGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        //количество записей в атрибуте
        $min = $params['min'] ?? 1;
        $max = $params['max'] ?? 5;
        $count = mt_rand($min, $max);
        
        $macs = [];
        for ($i = 0; $i < $count; $i++) {
            $macs[] = self::randomMac();
        }
        
        //возвращаем строку с MAC через запятую
        return implode("\n", $macs);
    }

    /**
     * Генерирует случайный MAC адрес
     * @return string
     */
    public static function randomMac(): string
    {
        //генерируем локальный MAC адрес (бит 8-й бит = 1)
        //формат: XX:XX:XX:XX:XX:XX
        $bytes = [
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
        ];
        
        //устанавливаем локальный бит (2-й бит)
        $bytes[0] = $bytes[0] | 0x02;
        
        return implode(':', array_map(function($byte) {
            return strtoupper(sprintf('%02x', $byte));
        }, $bytes));
    }
}
