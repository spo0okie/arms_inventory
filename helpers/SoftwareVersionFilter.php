<?php

namespace app\helpers;

/** Применяет условия одного формата к одной переданной сборщиком версии. */
final class SoftwareVersionFilter
{
	public const OPERATORS = ['='=>'=', '<'=>'<', '<='=>'≤', '>'=>'>', '>='=>'≥'];

	/** Сравнивает значения согласно типу поля формата; null означает неподходящее значение. */
	private static function compare(string $type, string $a, string $b): ?int
	{
		if ($type === 'string') return strcmp($a, $b);
		if ($type !== 'integer' || !ctype_digit($a) || !ctype_digit($b)) return null;
		$a = ltrim($a, '0');
		$b = ltrim($b, '0');
		return (strlen($a) <=> strlen($b)) ?: strcmp($a, $b);
	}

	/** Проверяет структуру и значения введённых условий перед перебором установок. */
	public static function error(array $format, array $conditions): ?string
	{
		foreach ($conditions as $key=>$condition) {
			$field = $format['fields'][$key] ?? null;
			if (!$field || !is_array($condition) || !is_string($condition['value'] ?? null)
				|| !is_string($condition['op'] ?? null) || !isset(self::OPERATORS[$condition['op']])) {
				return 'Недопустимое условие сравнения.';
			}
			$value = trim($condition['value']);
			if ($value !== '' && self::compare($field[1], $value, $value) === null) return 'Проверьте значение поля «'.$field[0].'».';
		}
		return null;
	}

	/** Проверяет формат и все заполненные условия на одной строке версии. */
	public static function matches(string $version, array $format, array $conditions): bool
	{
		if (!preg_match($format['pattern'], $version, $parts)) return false;
		foreach ($conditions as $key=>$condition) {
			$value = trim($condition['value']);
			if ($value === '') continue;
			if (!isset($parts[$key])) return false;
			$cmp = self::compare($format['fields'][$key][1], $parts[$key], $value);
			if ($cmp === null || !match ($condition['op']) {
				'='=>$cmp === 0, '<'=>$cmp < 0, '<='=>$cmp <= 0, '>'=>$cmp > 0, '>='=>$cmp >= 0,
				default=>false,
			}) return false;
		}
		return true;
	}
}
