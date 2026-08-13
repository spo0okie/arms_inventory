<?php

namespace app\components\integrations;

use Yii;

/**
 * Файловый кэш отрендеренных панелей интеграций
 * (docs/dev/integrations.md).
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
	/**
	 * Путь файла кэша (привязка хэшируется: IP/DN в имя файла не годятся).
	 * Компактный рендер той же панели - отдельный файл: HTML у режимов
	 * разный, а привязка одна.
	 */
	public static function path(string $providerId, string $panelId, string $binding,
		bool $compact = false): string
	{
		return Yii::getAlias('@app').'/runtime/integrations_cache/'
			.basename($providerId).'/'.basename($panelId).'/'
			.md5($binding).($compact ? '.compact' : '').'.html';
	}

	/**
	 * Содержимое кэша и его возраст
	 * @return array|null ['html'=>string, 'age'=>int сек] либо null если кэша нет
	 */
	public static function fetch(string $providerId, string $panelId, string $binding,
		bool $compact = false): ?array
	{
		$path = static::path($providerId, $panelId, $binding, $compact);
		if (!is_file($path)) return null;
		$html = @file_get_contents($path);
		if ($html === false) return null;
		return ['html' => $html, 'age' => max(0, time() - filemtime($path))];
	}

	/** Атомарная запись (tmp + rename): ошибка рендера кэш не трогает */
	public static function store(string $providerId, string $panelId, string $binding, string $html,
		bool $compact = false): void
	{
		$path = static::path($providerId, $panelId, $binding, $compact);
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
