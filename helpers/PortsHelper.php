<?php

namespace app\helpers;

/**
 * Разбор текстового объявления портов.
 *
 * Строка = порт, порядок строк = порядок портов на устройстве. Это единственный
 * ответ на вопрос «где физически находится порт Gi1/0/13»: сама по себе
 * запись `ports` такого не знает (она появляется только под связь и порядка
 * не несёт), а по имени порядок не восстановить — `Gi1/0/0` бывает первым,
 * а `uplink2` на MikroTik не говорит ни о чём.
 *
 * Формат один и тот же у шаблона модели ({@see \app\models\TechModels::$ports})
 * и у объявления конкретного устройства
 * ({@see \app\models\Techs::$ports_override}): первое слово — имя порта,
 * остальное — комментарий («сгорел», «в патч-панель 3»).
 */
class PortsHelper
{
	/** Направления заполнения корпуса */
	const DIR_DOWN = 'down';
	const DIR_RIGHT = 'right';

	/** Слова, которыми направление пишут в объявлении */
	const DIRECTIONS = [
		'вниз' => self::DIR_DOWN, 'down' => self::DIR_DOWN,
		'вправо' => self::DIR_RIGHT, 'right' => self::DIR_RIGHT,
	];

	/**
	 * Геометрия корпуса модели: строка = блок портов.
	 *
	 * Формат: `<столбцов>x<рядов> [направление] [подпись]`, например
	 *
	 *     12x2 вниз Основные
	 *     4 SFP
	 *
	 * 12x2 — сетка 12 на 2, то есть 24 порта в два ряда; просто 4 — один ряд.
	 * Размер читается как размер сетки, потому что именно так его читает
	 * человек: «24x2» ждут увидеть двумя рядами по 24.
	 *
	 * Блоки идут в том же порядке, что и объявленные порты, и «съедают» их
	 * по очереди: первый блок — первые 24 имени, второй — следующие 4. Имена
	 * тут не повторяются намеренно: геометрия — свойство модели (у всех
	 * экземпляров корпус один), а имена живут у экземпляра и после
	 * стекирования меняются.
	 *
	 * Направление — как считает нумерация внутри блока: `вниз` (по умолчанию)
	 * значит «первый порт сверху слева, второй под ним», как на большинстве
	 * коммутаторов; `вправо` — «весь верхний ряд, потом нижний».
	 *
	 * @return array [['count'=>int,'rows'=>int,'dir'=>string,'title'=>string], ...]
	 */
	public static function parseLayout(?string $text): array
	{
		$blocks = [];
		foreach (explode("\n", (string)$text) as $line) {
			$line = trim($line);
			if (!strlen($line) || $line[0] === '#') continue;

			$tokens = preg_split('~\s+~', $line);
			//первый токен - размер блока: 24x2 = 24 столбца по 2 ряда (48 портов),
			//просто 4 = один ряд. Читается как размеры сетки, а не «портов x рядов»:
			//человек, увидев 24x2, ждёт два ряда по 24
			if (!preg_match('~^(\d+)(?:[xх*](\d+))?$~ui', array_shift($tokens), $size)) continue;

			$columns = (int)$size[1];
			$rows = isset($size[2]) ? (int)$size[2] : 1;
			if ($columns < 1 || $rows < 1) continue;
			$count = $columns * $rows;

			//направление, если названо, иначе всё остальное - подпись блока
			$dir = self::DIR_DOWN;
			if (count($tokens) && isset(self::DIRECTIONS[mb_strtolower($tokens[0])])) {
				$dir = self::DIRECTIONS[mb_strtolower(array_shift($tokens))];
			}

			$blocks[] = [
				'count' => $count,
				'rows' => min($rows, $count),
				'dir' => $dir,
				'title' => trim(implode(' ', $tokens)),
			];
		}
		return $blocks;
	}

	/**
	 * Короткая подпись слота на карте портов.
	 *
	 * В квадратик размером с палец влезает номер, а не «GigabitEthernet1/0/13»,
	 * поэтому берём последнее число имени; если чисел нет вовсе (`uplink`,
	 * `mgmt`) — первые символы. Полное имя показывает подсказка.
	 */
	public static function slotLabel(string $name): string
	{
		if (preg_match('~(\d+)\s*$~', $name, $found)) return $found[1];
		return mb_substr(trim($name), 0, 3);
	}

	/**
	 * Разложить порты по слотам корпуса.
	 *
	 * @param array $blocks геометрия {@see parseLayout()}
	 * @param array $ports имена портов в объявленном порядке
	 * @return array [['title'=>string,'grid'=>[ряд][колонка] => имя порта|null], ...];
	 *   порты, которым не хватило геометрии, уходят в последний блок одним
	 *   рядом - объявление и корпус расходятся, и прятать это не надо
	 */
	public static function layoutSlots(array $blocks, array $ports): array
	{
		$ports = array_values($ports);
		$slots = [];

		foreach ($blocks as $block) {
			$names = array_splice($ports, 0, $block['count']);
			if (!count($names)) break;

			$columns = (int)ceil($block['count'] / $block['rows']);
			$grid = array_fill(0, $block['rows'], array_fill(0, $columns, null));

			foreach ($names as $index => $name) {
				//вниз: первый порт сверху слева, второй под ним (как на железе);
				//вправо: сначала весь верхний ряд
				if ($block['dir'] === self::DIR_DOWN) {
					$row = $index % $block['rows'];
					$column = intdiv($index, $block['rows']);
				} else {
					$row = intdiv($index, $columns);
					$column = $index % $columns;
				}
				if (isset($grid[$row][$column])) continue;
				$grid[$row][$column] = $name;
			}

			$slots[] = ['title' => $block['title'], 'grid' => $grid];
		}

		//лишние порты: геометрия описывает меньше, чем объявлено
		if (count($ports)) {
			$slots[] = ['title' => 'вне раскладки', 'grid' => [array_values($ports)]];
		}
		return $slots;
	}

	/**
	 * Текстовое объявление -> [имя порта => комментарий] в порядке объявления.
	 *
	 * @param string|null $text строки вида «Gi1/0/13 сгорел»
	 * @return array сохраняет порядок; дубли имён схлопываются (побеждает первое)
	 */
	public static function parseList(?string $text): array
	{
		$ports = [];
		foreach (explode("\n", (string)$text) as $line) {
			$tokens = explode(' ', trim($line));

			//первое слово - имя порта, остальные - комментарий к нему
			$name = trim(array_shift($tokens));
			if (!strlen($name)) continue;
			if (isset($ports[$name])) continue;

			$ports[$name] = trim(implode(' ', $tokens));
		}
		return $ports;
	}
}
