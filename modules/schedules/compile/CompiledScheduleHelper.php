<?php

namespace app\modules\schedules\compile;

/**
 * PHP-рантайм для работы со скомпилированным расписанием (compiled_json).
 *
 * Порт JS-класса `ScheduleRuntime` из modules/schedules/compile/lib/js/demo.js.
 * Контракт структуры — в modules/schedules/compile/compile.md.
 *
 * Все `_tsm` — минуты от UTC-epoch. Правые границы интервалов и периодов исключены
 * (формула `[start, end)`), см. "Ограничения консистентности данных" в плане.
 */
class CompiledScheduleHelper
{
	public const MINUTES_IN_DAY = 1440;

	/** @var array */
	private $schedule;
	/** @var array */
	private $main;
	/** @var array */
	private $overrides;

	/**
	 * @param array|string $compiled массив или JSON-строка из compiled_json
	 */
	public function __construct($compiled)
	{
		if (is_string($compiled)) {
			$compiled = json_decode($compiled, true) ?: [];
		}
		$this->schedule = is_array($compiled) ? $compiled : [];
		$this->main      = $this->schedule['main']      ?? [];
		$this->overrides = $this->schedule['overrides'] ?? [];
	}

	// =========================================================================
	// ПУБЛИЧНЫЙ API
	// =========================================================================

	public function isWorkDay(?string $date): bool
	{
		$tsm = self::strToTsm($date);
		if ($tsm === null) return false;
		return count($this->getDateIntervals($tsm)) > 0;
	}

	public function isWorkTime(?string $dateTime): bool
	{
		$tsm = self::strToTsm($dateTime);
		if ($tsm === null) return false;
		$intervals = $this->getDateIntervals($tsm);
		return self::intervalsContains($intervals, $tsm) !== null;
	}

	/**
	 * @return array|null meta найденного интервала или null
	 */
	public function getMeta(?string $dateTime): ?array
	{
		$tsm = self::strToTsm($dateTime);
		if ($tsm === null) return null;
		$intervals = $this->getDateIntervals($tsm);
		$interval = self::intervalsContains($intervals, $tsm);
		if ($interval === null) return null;
		$meta = $interval[2] ?? null;
		if (is_object($meta)) $meta = (array)$meta;
		return is_array($meta) ? $meta : [];
	}

	public function nextWorkingDateTime(?string $dateTime): ?string
	{
		$pos = self::strToTsm($dateTime);
		if ($pos === null) return null;

		$mainStart = $this->main['start_tsm'] ?? null;
		if ($mainStart !== null && $pos < $mainStart) $pos = $mainStart;

		while (self::inBounds($pos, $this->main)) {
			// Пропускаем нерабочий период
			$nonWork = $this->findPeriod($pos, false);
			if ($nonWork !== null) {
				$pos = $nonWork['end_tsm'];
				continue;
			}

			$target = $this->findOverride($pos);
			$entry = $this->nextRecord($pos, $target);
			if ($entry === null) {
				$end = $target['end_tsm'] ?? null;
				if ($end !== null) {
					$pos = $end + 1;
					continue;
				}
				return null;
			}

			$type = $entry['type'] ?? null;
			if ($type === 'period') {
				return self::tsmToStr($entry['start_tsm']);
			}
			if ($type === 'override') {
				$pos = $entry['start_tsm'];
				continue;
			}
			// weekday / date: кандидат указывает на день; итоговый график дня
			// считаем через getDateIntervals (учитывает дату-исключение, периоды и
			// приоритет над перекрытием). Если день нерабочий — переходим к следующему.
			$dayStart = $entry['date_tsm'] ?? self::tsmToDateTsm($pos);
			$intervals = $this->getDateIntervals($dayStart);
			$minutesFromPos = ($dayStart === self::tsmToDateTsm($pos)) ? ($pos - $dayStart) : -1;
			$minStart = null;
			foreach ($intervals as $int) {
				if ($int[1] > $minutesFromPos) {
					$s = $dayStart + $int[0];
					if ($minStart === null || $s < $minStart) $minStart = $s;
				}
			}
			if ($minStart === null) {
				$pos = $dayStart + self::MINUTES_IN_DAY;
				continue;
			}
			return self::tsmToStr(max($pos, $minStart));
		}
		return null;
	}

	/**
	 * @return array|null meta ближайшего рабочего интервала (или null)
	 */
	public function nextWorkingMeta(?string $dateTime): ?array
	{
		$next = $this->nextWorkingDateTime($dateTime);
		if ($next === null) return null;
		$tsm = self::strToTsm($dateTime);
		$nextTsm = self::strToTsm($next);
		if ($tsm !== null && $nextTsm !== null && $nextTsm < $tsm) {
			return $this->getMeta(self::tsmToStr($tsm));
		}
		return $this->getMeta($next);
	}

	// =========================================================================
	// ВНУТРЕННИЕ АЛГОРИТМИЧЕСКИЕ МЕТОДЫ
	// =========================================================================

	public function getDateIntervals(int $tsm): array
	{
		$dateTsm = self::tsmToDateTsm($tsm);
		if ($dateTsm === null) return [];
		if (!self::inBounds($dateTsm, $this->main)) return [];

		// Дата-исключение из main имеет приоритет над перекрытием (дни-исключения
		// существуют только в main). Иначе — недельный график из override/main.
		$base = $this->getDateExceptionIntervals($dateTsm);
		if ($base === null) {
			$target = $this->findOverride($dateTsm);
			$base = $this->getEntryIntervals($target, $dateTsm);
		}
		$periods = $this->getDatePeriodsIntervals($dateTsm);
		return $this->applyPeriodsToDay($base, $periods);
	}

	/**
	 * Интервалы дня-исключения из main или null, если на эту дату исключения нет.
	 * Дни-исключения существуют только в main и имеют приоритет над перекрытием.
	 */
	public function getDateExceptionIntervals(int $dateTsm): ?array
	{
		$dates = $this->main['dates'] ?? [];
		if (is_object($dates)) $dates = (array)$dates;
		$dKey = (string)$dateTsm;
		if (!isset($dates[$dKey])) return null;
		$entry = $dates[$dKey];
		if (is_object($entry)) $entry = (array)$entry;
		return $entry['intervals'] ?? [];
	}

	public function getDatePeriods(int $dateTsm): array
	{
		$dayStart = self::tsmToDateTsm($dateTsm);
		$dayEnd = $dayStart + self::MINUTES_IN_DAY;
		$result = [];
		$periods = $this->main['periods'] ?? [];
		foreach ($periods as $p) {
			// [start, end) ∩ [dayStart, dayEnd) ≠ ∅; null = безграничность (как в inBounds)
			$start = $p['start_tsm'] ?? null;
			$end = $p['end_tsm'] ?? null;
			if (($end === null || $end > $dayStart) && ($start === null || $start < $dayEnd)) {
				$result[] = $p;
			}
		}
		return $result;
	}

	public function getDatePeriodsIntervals(int $dateTsm): array
	{
		$periods = $this->getDatePeriods($dateTsm);
		$dayStart = self::tsmToDateTsm($dateTsm);
		$dayEnd = $dayStart + self::MINUTES_IN_DAY;
		$positive = [];
		$negative = [];
		foreach ($periods as $p) {
			// Обрезаем период по границам дня; null = безграничность.
			$ps = $p['start_tsm'] ?? null;
			$pe = $p['end_tsm'] ?? null;
			$s = $ps === null ? $dayStart : max($ps, $dayStart);
			$e = $pe === null ? $dayEnd : min($pe, $dayEnd);
			$meta = $p['meta'] ?? [];
			if (is_object($meta)) $meta = (array)$meta;
			$interval = [$s - $dayStart, $e - $dayStart, $meta];
			if (!empty($p['is_work'])) $positive[] = $interval;
			else $negative[] = $interval;
		}
		return ['positive' => $positive, 'negative' => $negative];
	}

	public function applyPeriodsToDay(array $baseIntervals, array $periods): array
	{
		$intervals = $baseIntervals;
		foreach ($periods['negative'] ?? [] as $neg) {
			$intervals = self::intervalsSubtract($intervals, $neg);
		}
		foreach ($periods['positive'] ?? [] as $pos) {
			$intervals = self::intervalsAdd($intervals, $pos);
		}
		return $intervals;
	}

	public function findOverride(int $tsm): array
	{
		foreach ($this->overrides as $ov) {
			if (self::inBounds($tsm, $ov)) return $ov;
		}
		return $this->main;
	}

	public function nextOverride(int $tsm): ?array
	{
		foreach ($this->overrides as $ov) {
			if (($ov['start_tsm'] ?? 0) >= $tsm) return $ov;
		}
		return null;
	}

	public function findPeriod(int $tsm, ?bool $isWork = null): ?array
	{
		foreach ($this->main['periods'] ?? [] as $p) {
			if (!self::inBounds($tsm, $p)) continue;
			if ($isWork !== null && $isWork !== (bool)$p['is_work']) continue;
			return $p;
		}
		return null;
	}

	public function nextPeriod(int $tsm, ?bool $isWork = null): ?array
	{
		foreach ($this->main['periods'] ?? [] as $p) {
			if (($p['end_tsm'] ?? 0) <= $tsm) continue;
			if ($isWork !== null && $isWork !== (bool)$p['is_work']) continue;
			return $p;
		}
		return null;
	}

	/**
	 * Недельный график target на дату: weekdays[dow] → default → [].
	 * Дни-исключения здесь НЕ проверяются — они берутся из main в getDateIntervals.
	 */
	public function getEntryIntervals(array $target, int $dateTsm): array
	{
		$wdKey = (string)self::dayOfWeek($dateTsm);
		$weekdays = $target['weekdays'] ?? [];
		if (is_object($weekdays)) $weekdays = (array)$weekdays;
		if (isset($weekdays[$wdKey])) {
			$entry = $weekdays[$wdKey];
			if (is_object($entry)) $entry = (array)$entry;
			return $entry['intervals'] ?? [];
		}

		if (!empty($target['default'])) {
			$def = $target['default'];
			if (is_object($def)) $def = (array)$def;
			return $def['intervals'] ?? [];
		}
		return [];
	}

	public function filterBefore(array $entry, int $tsm): array
	{
		$tsmDate = self::tsmToDateTsm($tsm);
		if (($entry['date_tsm'] ?? null) !== $tsmDate) return $entry;
		$minutesFromDay = $tsm - $tsmDate;
		$entry['intervals'] = array_values(array_filter(
			$entry['intervals'] ?? [],
			static fn($int) => $int[1] > $minutesFromDay
		));
		return $entry;
	}

	public function nextWorkDateEntry(int $tsm, array $target): ?array
	{
		$dates = $target['dates'] ?? [];
		if (is_object($dates)) $dates = (array)$dates;
		if (empty($dates)) return null;
		$tsmDate = self::tsmToDateTsm($tsm);
		$keys = array_map('intval', array_keys($dates));
		sort($keys);
		foreach ($keys as $dateTsm) {
			$entry = $dates[(string)$dateTsm];
			if (is_object($entry)) $entry = (array)$entry;
			$intervals = $entry['intervals'] ?? [];
			if (empty($intervals)) continue;
			$dayEndTsm = $dateTsm + self::MINUTES_IN_DAY;
			if ($dayEndTsm <= $tsm) continue;
			$entry['date_tsm'] = $dateTsm;
			if ($dateTsm === $tsmDate) {
				$filtered = $this->filterBefore($entry, $tsm);
				if (!empty($filtered['intervals'])) return $filtered;
				continue;
			}
			return $entry;
		}
		return null;
	}

	public function nextWeekDayEntry(int $pos, array $target): ?array
	{
		$wdNum = self::dayOfWeek($pos);
		$dayStart = self::tsmToDateTsm($pos);
		$weekdays = $target['weekdays'] ?? [];
		if (is_object($weekdays)) $weekdays = (array)$weekdays;
		$default = $target['default'] ?? null;
		if (is_object($default)) $default = (array)$default;

		for ($i = 0; $i < 7; $i++) {
			$checkDay = (($wdNum + $i - 1) % 7) + 1;
			$wd = $weekdays[(string)$checkDay] ?? null;
			if (is_object($wd)) $wd = (array)$wd;
			$entry = $wd ?: $default;
			$tsmDate = $dayStart + ($i * self::MINUTES_IN_DAY);
			if (!$entry || empty($entry['intervals'])) continue;

			$withDate = $entry + ['date_tsm' => $tsmDate];
			$withDate['date_tsm'] = $tsmDate;
			$filtered = $this->filterBefore($withDate, $pos);
			if (!empty($filtered['intervals'])) return $filtered;
		}
		return null;
	}

	public function nextRecord(int $pos, array $target): ?array
	{
		$isMain = ($target === $this->main);
		$candidates = [];

		$period = $this->nextPeriod($pos, true);
		if ($period !== null) {
			$period['type'] = 'period';
			$candidates[] = $period;
		}

		$weekEntry = $this->nextWeekDayEntry($pos, $target);
		if ($weekEntry !== null) {
			$weekEntry['type'] = 'weekday';
			$weekEntry['start_tsm'] = $weekEntry['date_tsm'];
			$candidates[] = $weekEntry;
		}

		// Дни-исключения существуют только в main и имеют приоритет над перекрытием —
		// рассматриваем их как кандидата всегда, даже когда pos попал в окно override.
		$dateEntry = $this->nextWorkDateEntry($pos, $this->main);
		if ($dateEntry !== null) {
			$dateEntry['type'] = 'date';
			$dateEntry['start_tsm'] = $dateEntry['date_tsm'];
			$candidates[] = $dateEntry;
		}

		if ($isMain) {
			$override = $this->nextOverride($pos);
			if ($override !== null) {
				$override['type'] = 'override';
				$candidates[] = $override;
			}
		}

		if (empty($candidates)) return null;
		usort($candidates, static fn($a, $b) => $a['start_tsm'] <=> $b['start_tsm']);
		return $candidates[0];
	}

	// =========================================================================
	// ВСПОМОГАТЕЛЬНЫЕ СТАТИЧЕСКИЕ ФУНКЦИИ
	// =========================================================================

	public static function strToTsm(?string $str): ?int
	{
		return SchedulesCompiler::strToTsm($str);
	}

	public static function tsmToStr(?int $tsm): ?string
	{
		if ($tsm === null) return null;
		$ts = $tsm * 60;
		return gmdate('Y-m-d H:i', $ts);
	}

	public static function tsmToDateTsm(?int $tsm): ?int
	{
		if ($tsm === null) return null;
		return $tsm - ($tsm % self::MINUTES_IN_DAY);
	}

	/**
	 * День недели: 1=пн, 7=вс.
	 */
	public static function dayOfWeek(?int $tsm): ?int
	{
		if ($tsm === null) return null;
		$ts = $tsm * 60;
		// gmdate('N'): 1..7 для пн..вс
		return (int)gmdate('N', $ts);
	}

	public static function inBounds(?int $tsm, $bounds): bool
	{
		if ($tsm === null || !is_array($bounds)) return false;
		$start = $bounds['start_tsm'] ?? null;
		$end = $bounds['end_tsm'] ?? null;
		if ($start !== null && $tsm < $start) return false;
		if ($end !== null && $tsm >= $end) return false;
		return true;
	}

	/**
	 * Интервал, содержащий tsm в контексте его дня (минуты от начала суток).
	 *
	 * @return array|null [start, end, meta] или null
	 */
	public static function intervalsContains(array $intervals, int $tsm): ?array
	{
		if (empty($intervals)) return null;
		$dayStart = self::tsmToDateTsm($tsm);
		$minutesFromDay = $tsm - $dayStart;
		foreach ($intervals as $int) {
			if ($minutesFromDay >= $int[0] && $minutesFromDay < $int[1]) return $int;
		}
		return null;
	}

	public static function intervalsSubtract(array $intervals, $subtract): array
	{
		if (empty($intervals)) return [];
		if (!$subtract || $subtract[1] <= $subtract[0]) return $intervals;
		$sS = $subtract[0];
		$sE = $subtract[1];
		$result = [];
		foreach ($intervals as $int) {
			[$iS, $iE, $meta] = [$int[0], $int[1], $int[2] ?? []];
			if ($sE <= $iS || $sS >= $iE) { $result[] = $int; continue; }
			if ($sS <= $iS && $sE >= $iE) continue; // full cover
			if ($sS > $iS && $sE >= $iE) { $result[] = [$iS, $sS, $meta]; continue; }
			if ($sS <= $iS && $sE < $iE) { $result[] = [$sE, $iE, $meta]; continue; }
			// splits in two
			$result[] = [$iS, $sS, $meta];
			$result[] = [$sE, $iE, $meta];
		}
		return $result;
	}

	public static function intervalsAdd(array $intervals, $override): array
	{
		if (empty($intervals)) return $override ? [$override] : [];
		if (!$override || $override[1] <= $override[0]) return $intervals;
		$result = self::intervalsSubtract($intervals, $override);
		$result[] = $override;
		return $result;
	}
}
