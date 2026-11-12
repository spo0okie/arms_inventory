<?php

namespace app\helpers;

use yii\db\Expression;

/** Общие правила сопоставления телефонных номеров в PHP и запросах MariaDB. */
class PhoneHelper
{
	/** Удаляет оформление и приводит российский префикс 8 к международному 7. */
	public static function normalize($number): string
	{
		$digits = preg_replace('/[^0-9]/', '', (string)$number);
		return strlen($digits) === 11 && $digits[0] === '8' ? '7'.substr($digits, 1) : $digits;
	}

	/** SQL-эквивалент normalize. Имя поля передаёт только код приложения. */
	public static function normalizedColumn(string $column): Expression
	{
		$digits = "REGEXP_REPLACE(COALESCE([[$column]], ''), '[^0-9]', '')";
		return new Expression("CASE WHEN LENGTH($digits) = 11 AND LEFT($digits, 1) = '8' THEN CONCAT('7', SUBSTRING($digits, 2)) ELSE $digits END");
	}

	/** Точное совпадение по одному из полей. Пустой номер никогда не совпадает. */
	public static function exactCondition(array $columns, $number): array
	{
		$number = self::normalize($number);
		if ($number === '') return ['=', new Expression('1'), 0];
		$condition = ['or'];
		foreach ($columns as $column) {
			$condition[] = ['=', self::normalizedColumn($column), $number];
		}
		return $condition;
	}

	/** Поиск в гриде с сохранением операторов существующего строкового фильтра. */
	public static function searchCondition(array $columns, $search): array
	{
		return QueryHelper::tokenizeString($search ?? '', $columns, static function ($token, $fields) {
			// Служебные запросы и текст сохраняют прежнее поведение.
			if (!preg_match('/[0-9]/', $token) || !preg_match('/^[!^$+0-9\s().\x{00a0}\x{2010}-\x{2015}-]+$/u', $token)) {
				return QueryHelper::likeToken($token, array_merge(['OR'], $fields));
			}
			$token = trim($token);
			$negative = str_starts_with($token, '!');
			$value = ltrim($token, '! ');
			$normalized = self::normalize($value);
			$pattern = ($negative ? '!' : '').(str_starts_with($value, '^') ? '^' : '').$normalized.(str_ends_with($value, '$') ? '$' : '');
			$expressions = array_map([self::class, 'normalizedColumn'], $fields);
			return QueryHelper::likeToken($pattern, array_merge(['OR'], $expressions));
		});
	}
}
