<?php

namespace app\generation\generators;

/**
 * Генератор для типа macs (список MAC адресов)
 */
class MacsGenerator implements GeneratorInterface
{
    public function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

        //генерируем список MAC адресов
        //по умолчанию 1-3 MAC адреса
        $count = $params['count'] ?? random_int(1, 3);
        
        $macs = [];
        for ($i = 0; $i < $count; $i++) {
            $macs[] = $this->randomMac();
        }
        
        //возвращаем строку с MAC через запятую
        return implode(', ', $macs);
    }

    /**
     * Генерирует случайный MAC адрес
     * @return string
     */
    protected function randomMac(): string
    {
        //генерируем локальный MAC адрес (бит 8-й бит = 1)
        //формат: XX:XX:XX:XX:XX:XX
        $bytes = [
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255),
        ];
        
        //устанавливаем локальный бит (2-й бит)
        $bytes[0] = $bytes[0] | 0x02;
        
        return implode(':', array_map(function($byte) {
            return strtoupper(sprintf('%02x', $byte));
        }, $bytes));
    }
}
