<?php

namespace app\helpers;

/**
 * Хелпер работы с цветом (HEX #RRGGBB / #RGB).
 * Единая точка для подбора контрастного цвета текста —
 * используется моделями (Tags, Markers) и типом ColorType.
 */
class ColorHelper
{
	/**
	 * Проверка валидности HEX цвета (#RRGGBB или #RGB)
	 * @param string|null $color
	 * @return bool
	 */
	public static function isValidHex(?string $color): bool
	{
		return is_string($color)
			&& (bool)preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color);
	}

	/**
	 * Контрастный цвет текста (#000000 / #ffffff) к цвету фона.
	 * Яркость — взвешенная сумма каналов (Rec.601 / YIQ).
	 *
	 * Порог 0.6, а не «нейтральный» 0.5: на средних тонах (серый #808080,
	 * зелёный #5CB85C, синий #1DA7EE) белый текст перцептивно читается
	 * лучше, хотя формальный контраст у чёрного там чуть выше (эффект
	 * полярности); светлые фоны (жёлтый, циан, пастель) остаются с чёрным.
	 * @param string $hexColor HEX цвет фона
	 * @return string '#000000' или '#ffffff'
	 */
	public static function contrastColor(string $hexColor): string
	{
		$hex = ltrim($hexColor, '#');

		//расширяем 3-символьный цвет до 6-символьного
		if (strlen($hex) === 3) {
			$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		}

		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));

		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

		return $luminance > 0.6 ? '#000000' : '#ffffff';
	}
}
