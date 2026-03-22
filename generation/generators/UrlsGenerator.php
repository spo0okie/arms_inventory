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

		//детерминизм
		if ($params['seed'] !== null) {
 			mt_srand($params['seed']);
		}

        //количество записей в атрибуте
        $min = $params['min'] ?? 1;
        $max = $params['max'] ?? 5;
        $count = mt_rand($min, $max);
        
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
        
        $domain = $domains[mt_rand(0, count($domains) - 1)];
        $path = $paths[mt_rand(0, count($paths) - 1)];
        
        //добавляем случайный протокол
        $protocol = mt_rand(0, 1) === 0 ? 'http' : 'https';
        
        return "{$protocol}://{$domain}{$path}";
    }
}
