<?php

namespace app\modules\schedules\compile;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;

/**
 * Компилятор расписаний в плоский JSON, описанный в modules/schedules/compile/compile.md.
 *
 * Все временные метки (`_tsm`) хранятся в UTC-минутах от Unix epoch.
 * Строковые значения дат исходного расписания интерпретируются как UTC.
 */
class SchedulesCompiler
{
	public const MINUTES_IN_DAY = 1440;

	/**
	 * Допустимые weekday-ключи в SchedulesEntries.date (1..7 + 'def').
	 */
	private const WEEKDAY_KEYS = ['def', '1', '2', '3', '4', '5', '6', '7'];

	/**
	 * Скомпилировать расписание в массив, готовый к сериализации в compiled_json.
	 */
	public static function compile(Schedules $schedule): array
	{
		$main = self::buildEntity($schedule, true);

		$overrides = [];
		foreach ($schedule->overrides as $override) {
			$overrides[] = self::buildEntity($override, false);
		}
		usort($overrides, static fn($a, $b) => ($a['start_tsm'] ?? PHP_INT_MIN) <=> ($b['start_tsm'] ?? PHP_INT_MIN));

		return [
			'tz'           => 'UTC',
			'tz_shift_tsm' => self::tzShiftMinutes(),
			'compiled'     => gmdate('Y-m-d\TH:i:s\Z'),
			'main'         => $main,
			'overrides'    => $overrides,
		];
	}

	/**
	 * Сдвиг часового пояса расписаний в минутах от UTC.
	 * Берётся из `Yii::$app->params['schedulesTZShift']` (в секундах), безопасно падает в 0.
	 *
	 * Смысл: все строковые даты расписаний (start/end/date/date_end) и времена
	 * (HH:MM в графике) трактуются как время **этого** часового пояса. В compiled_json
	 * они записаны как «локальное время, интерпретированное как UTC» — это позволяет
	 * рантайму работать только с арифметикой минут без привязки к часовым поясам.
	 * Клиентский рантайм использует `tz_shift_tsm`, чтобы перевести реальный UTC now
	 * в ту же систему координат.
	 */
	public static function tzShiftMinutes(): int
	{
		try {
			$seconds = (int)(\Yii::$app->params['schedulesTZShift'] ?? 0);
		} catch (\Throwable $e) {
			$seconds = 0;
		}
		return intdiv($seconds, 60);
	}

	/**
	 * Построить структуру entity — main-расписание или override.
	 * Periods включаются только в main (по плану).
	 *
	 * Для main-расписания собирается цепочка предков через `parent_id` (без overrides): запись
	 * текущего расписания переопределяет запись предка по тому же ключу (weekday/date/def).
	 * Override-расписания не наследуют ничего (работают изолированно).
	 */
	private static function buildEntity(Schedules $schedule, bool $includePeriods): array
	{
		$default = null;
		$weekdays = [];
		$dates = [];
		$periods = [];

		// Наследование по parent_id действует только для "чистого" main (не override).
		// При компиляции override, даже если он передан как $schedule, он не наследует
		// записи родителя — override изолирован.
		$sources = ($includePeriods && !$schedule->isOverride)
			? self::ancestorChain($schedule) // от self к root — для main
			: [$schedule];                   // override — только сам

		foreach ($sources as $source) {
			foreach ($source->entries as $entry) {
				if ($entry->is_period) {
					if ($includePeriods) {
						$periods[] = self::buildPeriod($entry);
					}
					continue;
				}
				$key = (string)$entry->date;
				if ($key === 'def') {
					// Дочерний `def` переопределяет родительский
					if ($default === null) $default = self::buildEntry($entry);
					continue;
				}
				if (in_array($key, self::WEEKDAY_KEYS, true)) {
					if (!isset($weekdays[$key])) $weekdays[$key] = self::buildEntry($entry);
					continue;
				}
				// Конкретная дата — ключ в минутах от epoch (начало дня)
				$dateTsm = self::strToDateTsm($key);
				if ($dateTsm === null) continue;
				$dKey = (string)$dateTsm;
				if (isset($dates[$dKey])) continue;
				$entryData = self::buildEntry($entry);
				$entryData['date_tsm'] = $dateTsm;
				$dates[$dKey] = $entryData;
			}
		}

		// Сортировки
		ksort($weekdays, SORT_NUMERIC);
		ksort($dates, SORT_NUMERIC);
		usort($periods, static fn($a, $b) => $a['start_tsm'] <=> $b['start_tsm']);

		$result = [
			'name'      => $schedule->name,
			'start'     => $schedule->start_date ?: null,
			'start_tsm' => self::strToTsm($schedule->start_date),
			'end'       => $schedule->end_date ?: null,
			'end_tsm'   => self::strToTsm($schedule->end_date),
			'default'   => $default,
			// Строковые ключи гарантируют object в JSON; для пустых — явно stdClass.
			'weekdays'  => $weekdays === [] ? new \stdClass() : $weekdays,
			'dates'     => $dates === [] ? new \stdClass() : $dates,
			'comment'   => $schedule->description,
		];
		if ($includePeriods) {
			$result['periods'] = $periods;
		}
		return $result;
	}

	/**
	 * Собрать цепочку расписаний от `$schedule` к корню по `parent_id`.
	 * Overrides из цепочки исключаются — они не участвуют в наследовании main.
	 * Защита от циклов: ограничение по глубине 100.
	 *
	 * @return Schedules[]
	 */
	private static function ancestorChain(Schedules $schedule): array
	{
		$chain = [];
		$seen = [];
		$current = $schedule;
		$limit = 100;
		while ($current !== null && $limit-- > 0) {
			if (isset($seen[$current->id])) break;
			$seen[$current->id] = true;
			if (!$current->isOverride) $chain[] = $current;
			$current = $current->parent ?? null;
		}
		return $chain;
	}

	/**
	 * Построить структуру entry (weekday/date/default).
	 */
	private static function buildEntry(SchedulesEntries $entry): array
	{
		$schedule = trim((string)$entry->schedule);
		return [
			'schedule'  => $schedule,
			'intervals' => self::parseSchedule($schedule),
			'comment'   => (string)$entry->comment,
		];
	}

	/**
	 * Построить структуру period.
	 */
	private static function buildPeriod(SchedulesEntries $entry): array
	{
		return [
			'start'     => $entry->date ?: null,
			'start_tsm' => self::strToTsm($entry->date),
			'end'       => $entry->date_end ?: null,
			'end_tsm'   => self::strToTsm($entry->date_end),
			'is_work'   => (bool)$entry->is_work,
			'comment'   => (string)$entry->comment,
			'meta'      => new \stdClass(),
		];
	}

	/**
	 * Распарсить текстовое расписание ("08:00-17:00,12:30-17:00{...}" или "-") в
	 * массив интервалов [[start_min, end_min, meta], ...], отсортированных по левой границе.
	 *
	 * Выход в минутах от начала дня (0..1440). Коллизии при наложении НЕ разрешаются
	 * на этом этапе — они обрабатываются рантаймом через applyPeriodsToDay при необходимости.
	 * Ожидается, что во входных данных валидных расписаний коллизий нет.
	 */
	public static function parseSchedule(?string $schedule): array
	{
		$schedule = trim((string)$schedule);
		if ($schedule === '' || $schedule === '-') return [];

		$intervals = [];
		foreach (explode(',', $schedule) as $token) {
			$token = trim($token);
			if ($token === '') continue;

			$meta = self::extractMeta($token);
			$clean = preg_replace('/\{[^}]*\}/', '', $token);
			$parts = explode('-', $clean);
			if (count($parts) !== 2) continue;

			$start = self::timeToMinutes($parts[0]);
			$end   = self::timeToMinutes($parts[1]);
			if ($start === null || $end === null) continue;
			if ($end <= $start) continue; //переходящие за полночь игнорируем — их нужно разбивать отдельно

			$intervals[] = [$start, $end, $meta === [] ? new \stdClass() : $meta];
		}

		usort($intervals, static fn($a, $b) => $a[0] <=> $b[0]);
		return $intervals;
	}

	/**
	 * Извлечь метаданные из `{...}` в токене графика.
	 * Если JSON невалиден — вернуть пустой массив.
	 */
	private static function extractMeta(string $token): array
	{
		if (!preg_match('/\{[^}]*\}/', $token, $m)) return [];
		$raw = $m[0];
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * "HH:MM" → минуты от начала суток или null.
	 */
	private static function timeToMinutes(string $time): ?int
	{
		$time = trim($time);
		if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) return null;
		$h = (int)$m[1];
		$min = (int)$m[2];
		if ($h > 23 || $min > 59) return null;
		return $h * 60 + $min;
	}

	/**
	 * "YYYY-MM-DD" или "YYYY-MM-DD HH:MM[:SS]" → минуты от UTC epoch (или null).
	 */
	public static function strToTsm(?string $str): ?int
	{
		if ($str === null) return null;
		$str = trim($str);
		if ($str === '') return null;

		// `!` в формате обнуляет неуказанные компоненты (секунды/минуты/часы),
		// иначе createFromFormat подставит текущее локальное время.
		$formats = ['!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d'];
		$tz = new \DateTimeZone('UTC');
		foreach ($formats as $fmt) {
			$dt = \DateTime::createFromFormat($fmt, $str, $tz);
			if ($dt === false) continue;
			$errors = \DateTime::getLastErrors();
			if ($errors && ($errors['error_count'] > 0 || $errors['warning_count'] > 0)) continue;
			return intdiv($dt->getTimestamp(), 60);
		}
		return null;
	}

	/**
	 * "YYYY-MM-DD" → минуты UTC начала дня, или null при невалидной дате.
	 */
	public static function strToDateTsm(?string $str): ?int
	{
		$tsm = self::strToTsm($str);
		if ($tsm === null) return null;
		return $tsm - ($tsm % self::MINUTES_IN_DAY);
	}
}
