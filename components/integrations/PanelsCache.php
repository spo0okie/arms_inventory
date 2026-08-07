<?php

namespace app\components\integrations;

use Yii;

/**
 * Файловый кэш отрендеренных панелей интеграций
 * (plans/integrations-contract.md §5).
 *
 * Файл на ФС: runtime/integrations_cache/<provider>/<panel>/<hash>.html.
 * Валидность не хранится — схема всегда «показать что есть → обновить»:
 * свежесть определяется сравнением filemtime() с ttl панели. Устаревшие
 * файлы не удаляются (нужны для мгновенного показа при следующем открытии
 * страницы). Кэш общий на инстанс: чтение всегда идёт от сервисной
 * учётки, два объекта с одной привязкой делят кэш.
 */
class PanelsCache
{
	/** Путь файла кэша (привязка хэшируется: IP/DN в имя файла не годятся) */
	public static function path(string $providerId, string $panelId, string $binding): string
	{
		return Yii::getAlias('@app').'/runtime/integrations_cache/'
			.basename($providerId).'/'.basename($panelId).'/'.md5($binding).'.html';
	}

	/**
	 * Содержимое кэша и его возраст
	 * @return array|null ['html'=>string, 'age'=>int сек] либо null если кэша нет
	 */
	public static function fetch(string $providerId, string $panelId, string $binding): ?array
	{
		$path = static::path($providerId, $panelId, $binding);
		if (!is_file($path)) return null;
		$html = @file_get_contents($path);
		if ($html === false) return null;
		return ['html' => $html, 'age' => max(0, time() - filemtime($path))];
	}

	/** Атомарная запись (tmp + rename): ошибка рендера кэш не трогает */
	public static function store(string $providerId, string $panelId, string $binding, string $html): void
	{
		$path = static::path($providerId, $panelId, $binding);
		$dir = dirname($path);
		if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
			Yii::warning("Can't create cache dir $dir", __METHOD__);
			return;
		}
		$tmp = $path.'.'.uniqid('tmp', true);
		if (@file_put_contents($tmp, $html) === false) return;
		if (!@rename($tmp, $path)) @unlink($tmp);
	}
}
