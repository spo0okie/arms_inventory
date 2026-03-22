<?php

namespace app\generation\generators;

/**
 * Генератор для типа ips (список IP адресов)
 */
class IpsGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

        //генерируем список IP адресов
        //по умолчанию 1-3 IP адреса
        $count = $params['count'] ?? random_int(1, 3);
        
        $ips = [];
        for ($i = 0; $i < $count; $i++) {
            $ips[] = self::randomIp();
        }
        
        //возвращаем набор строк с IP
        return implode("\n", $ips);
    }

    /**
     * Генерирует случайный IPv4 адрес
     * @return string
     */
    public static function randomIp(): string
    {
        //генерируем IP в частных сетях
        //10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
        $range = random_int(0, 2);
        
        switch ($range) {
            case 0:
                //10.0.0.0/8
                return sprintf('10.%d.%d.%d', 
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(1, 254)
                );
            case 1:
                //172.16.0.0/12
                return sprintf('172.%d.%d.%d', 
                    random_int(16, 31),
                    random_int(0, 255),
                    random_int(1, 254)
                );
            default:
                //192.168.0.0/16
                return sprintf('192.168.%d.%d', 
                    random_int(0, 255),
                    random_int(1, 254)
                );
        }
    }
}
