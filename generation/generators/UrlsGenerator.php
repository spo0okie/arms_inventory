<?php

namespace app\generation\generators;

/**
 * Генератор для типа urls (список URL адресов)
 */
class UrlsGenerator implements GeneratorInterface
{
    public static function generate(array $params): mixed
    {
        //если нужен пустой атрибут
        if ($params['empty']??false) {
            //если атрибут может быть null
            if ($params['nullable']??false) return null;
            
            return '';
        }

        //генерируем список URL
        //по умолчанию 1-3 URL
        $count = $params['count'] ?? random_int(1, 3);
        
        $urls = [];
        for ($i = 0; $i < $count; $i++) {
			$prefix=TextGenerator::randomWords(0,4,false);
			if ($prefix) $prefix.=' ';
            $urls[] = $prefix.self::randomUrl();
        }
        
        //возвращаем строку с URL через запятую
        return implode("\n", $urls);
    }

    /**
     * Генерирует случайный URL
     * @return string
     */
    public static function randomUrl(): string
    {
        //генерируем случайный домен и путь
        $domains = ['example.com', 'test.org', 'demo.net', 'site.ru', 'host.io'];
        $paths = ['/page', '/api/v1/resource', '/docs', '/blog/post', '/user/profile'];
        
        $domain = $domains[random_int(0, count($domains) - 1)];
        $path = $paths[random_int(0, count($paths) - 1)];
        
        //добавляем случайный протокол
        $protocol = random_int(0, 1) === 0 ? 'http' : 'https';
        
        return "{$protocol}://{$domain}{$path}";
    }
}
