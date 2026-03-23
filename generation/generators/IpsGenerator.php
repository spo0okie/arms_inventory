<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор IP-адресов
 */
class IpsGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(AttributeContext $context): mixed
    {
        // Режим пустых значений
        if ($context->generationContext->empty) {
            return $context->isNullable() ? null : '';
        }

        $config = $context->generatorConfig();

        // Детерминированная генерация
        $seed = $context->generationContext->seed + crc32($context->attribute);
        mt_srand($seed);

        $count = $config['count'] ?? 1;
        $result = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Приватные диапазоны: 10.x.x.x, 172.16-31.x.x, 192.168.x.x
            $range = $config['range'] ?? 'private';
            
            $ip = match ($range) {
                'private' => $this->generatePrivateIP(),
                'public' => $this->generatePublicIP(),
                'ipv6' => $this->generateIPv6(),
                default => $this->generatePrivateIP(),
            };
            
            $result[] = $ip;
        }

		mt_srand(); // сброс
		return implode("\n", $result);
    }

    private function generatePrivateIP(): string
    {
        $ranges = [
            [10, 10],           // 10.0.0.0/8
            [172, 16, 31],     // 172.16.0.0/12
            [192, 168],        // 192.168.0.0/16
        ];
        
        $selected = $ranges[mt_rand(0, count($ranges) - 1)];
        
        if (count($selected) === 2) {
            return sprintf('%d.%d.%d.%d', 
                $selected[0], 
                $selected[1], 
                mt_rand(1, 254), 
                mt_rand(1, 254)
            );
        } else {
            return sprintf('%d.%d.%d.%d', 
                $selected[0], 
                $selected[1], 
                $selected[2], 
                mt_rand(1, 254)
            );
        }
    }

    private function generatePublicIP(): string
    {
        return sprintf('%d.%d.%d.%d',
            mt_rand(1, 223),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(1, 254)
        );
    }

    private function generateIPv6(): string
    {
        $segments = [];
        for ($i = 0; $i < 8; $i++) {
            $segments[] = sprintf('%x', mt_rand(0, 65535));
        }
        return implode(':', $segments);
    }
}
