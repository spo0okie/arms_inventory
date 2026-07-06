<?php


namespace app\helpers;


use yii\db\Expression;

class MacsHelper
{
	/**
	 * Нормализует список MAC-адресов (по одному в строке).
	 * Поддерживает диапазоны «через тире» (issue #120): строка, которая сводится
	 * к 24 hex-символам, трактуется как пара MAC «начало-конец» и сохраняется
	 * КОМПАКТНО как "<12hex>-<12hex>" (не разворачивается). Однозначно, т.к. один
	 * MAC — это всегда 12 hex, а любые разделители (":", "-", ".") внутри адреса
	 * вырезаются.
	 *   Пример: "00:11:22:33:44:00 - 00:11:22:33:44:48" => "001122334400-001122334448".
	 *
	 * @param string $list
	 * @return string нормализованный список (адреса/диапазоны по одному в строке)
	 */
	public static function fixList($list) {
		$macs=[];
		foreach (explode("\n",$list) as $mac) {
			/* убираем посторонние символы из MAC*/
			$fixed=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));

			//диапазон: строка = 24 hex = два адреса (начало-конец), храним компактно
			if (strlen($fixed)===24) {
				$entry=static::normalizeRange(substr($fixed,0,12),substr($fixed,12,12));
				if ($entry!==null && array_search($entry,$macs)===false) $macs[]=$entry;
				continue;
			}

			//одиночный адрес (или частичный ввод) — прежнее поведение
			if (
				strlen($fixed) //не пустые
				&& array_search($fixed,$macs)===false //убираем дубликаты
				&& hexdec($fixed)>0	//убираем MAC вида 0000000
			)
				$macs[]=$fixed;
		}
		return implode("\n",$macs);
	}

	/**
	 * Приводит пару границ диапазона к компактной записи "start-end".
	 * Границы упорядочиваются (для 12-значного hex лексикографический порядок
	 * совпадает с числовым). Диапазон из одного адреса схлопывается в одиночный MAC.
	 * @param string $a 12 hex
	 * @param string $b 12 hex
	 * @return string|null запись диапазона/одиночного MAC либо null, если пусто
	 */
	private static function normalizeRange($a,$b) {
		if ($a>$b) { $t=$a; $a=$b; $b=$t; }
		if ($a===$b) return hexdec($a)>0 ? $a : null; //диапазон из одного адреса = одиночный
		return $a.'-'.$b;
	}

	/**
	 * SQL-условие «искомый MAC попадает в один из сохранённых диапазонов» (issue #120).
	 * Диапазоны хранятся компактно в многострочном текстовом поле, поэтому парсим
	 * его прямо в запросе через JSON_TABLE (полное сканирование; индексируемый кэш —
	 * задача #199). Применяется только для полного 12-значного MAC; частичный ввод
	 * ищется обычным LIKE.
	 *
	 * @param string[] $columns столбцы с MAC (напр. ['techs.mac','comps.mac'])
	 * @param string   $needle  искомое значение (уже нормализованное fixList)
	 * @return Expression|null условие или null, если искомое — не полный MAC
	 */
	public static function rangeMemberCondition(array $columns, $needle) {
		$hex=preg_replace('/[^0-9a-f]/', '', mb_strtolower((string)$needle));
		if (strlen($hex)!==12) return null; //вхождение проверяем только для полного MAC
		$n=hexdec($hex); //48 бит помещается в int на 64-битном PHP

		$parts=[];
		foreach ($columns as $col) {
			//строки поля -> JSON-массив -> по строкам; для строк-диапазонов (есть "-")
			//сравниваем численно границы (CONV hex->dec) с искомым значением
			$parts[]=
				"EXISTS (SELECT 1 FROM JSON_TABLE("
					."CONCAT('[\"',REPLACE($col,'\\n','\",\"'),'\"]'),"
					."'\$[*]' COLUMNS (line VARCHAR(32) PATH '\$')"
				.") mjt WHERE LOCATE('-',mjt.line)>0"
				." AND CAST(CONV(SUBSTRING_INDEX(mjt.line,'-',1),16,10) AS UNSIGNED)<=$n"
				." AND CAST(CONV(SUBSTRING_INDEX(mjt.line,'-',-1),16,10) AS UNSIGNED)>=$n)";
		}

		return new Expression('('.implode(' OR ',$parts).')');
	}
}
