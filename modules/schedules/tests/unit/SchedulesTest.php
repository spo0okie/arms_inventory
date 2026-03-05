<?php

namespace app\modules\schedules\tests\unit;

use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для модели Schedules.
 *
 * Тестируем только статические методы и константы — без БД.
 * Методы, требующие ActiveRecord (save, find и т.д.), не тестируются здесь.
 */
class SchedulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Статические свойства / константы
    // -------------------------------------------------------------------------

    public function testTableName(): void
    {
        $this->assertSame('schedules', Schedules::tableName());
    }

    public function testStaticTitles(): void
    {
        $this->assertNotEmpty(Schedules::$titles);
        $this->assertNotEmpty(Schedules::$title);
    }

    public function testAllDaysTitle(): void
    {
        $this->assertIsString(Schedules::$allDaysTitle);
        $this->assertNotEmpty(Schedules::$allDaysTitle);
    }

    public function testScenarioConstants(): void
    {
        $this->assertSame('scenario_override', Schedules::SCENARIO_OVERRIDE);
        $this->assertSame('scenario_acl', Schedules::SCENARIO_ACL);
    }

    // -------------------------------------------------------------------------
    // generatePeriodDescription
    // -------------------------------------------------------------------------

    /**
     * Оба значения null/0 → пустая строка.
     */
    public function testGeneratePeriodDescriptionBothNull(): void
    {
        $this->assertSame('', Schedules::generatePeriodDescription([0, 0]));
    }

    /**
     * Только начало → 'с YYYY-MM-DD'.
     */
    public function testGeneratePeriodDescriptionOnlyStart(): void
    {
        $ts = mktime(0, 0, 0, 6, 15, 2024);
        $result = Schedules::generatePeriodDescription([$ts, 0]);
        $this->assertStringStartsWith('с ', $result);
        $this->assertStringContainsString('2024-06-15', $result);
    }

    /**
     * Только конец → 'до YYYY-MM-DD'.
     */
    public function testGeneratePeriodDescriptionOnlyEnd(): void
    {
        $ts = mktime(0, 0, 0, 12, 31, 2024);
        $result = Schedules::generatePeriodDescription([0, $ts]);
        $this->assertStringStartsWith('до ', $result);
        $this->assertStringContainsString('2024-12-31', $result);
    }

    /**
     * Оба значения → 'с ... до ...'.
     */
    public function testGeneratePeriodDescriptionBothDates(): void
    {
        $start = mktime(0, 0, 0, 1, 1, 2024);
        $end   = mktime(0, 0, 0, 12, 31, 2024);
        $result = Schedules::generatePeriodDescription([$start, $end]);
        $this->assertStringContainsString('с ', $result);
        $this->assertStringContainsString(' до ', $result);
        $this->assertStringContainsString('2024-01-01', $result);
        $this->assertStringContainsString('2024-12-31', $result);
    }

    // -------------------------------------------------------------------------
    // dictionary
    // -------------------------------------------------------------------------

    /**
     * Словарь содержит все ожидаемые ключи.
     */
    public function testDictionaryHasRequiredKeys(): void
    {
        $requiredKeys = ['usage', 'usage_complete', 'usage_will_be', 'nodata', 'always'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, Schedules::$dictionary, "Словарь должен содержать ключ '$key'");
        }
    }

    /**
     * Каждый режим использования присутствует в ключе 'usage'.
     */
    public function testDictionaryUsageModes(): void
    {
        $modes = ['acl', 'providing', 'support', 'job', 'working'];
        foreach ($modes as $mode) {
            $this->assertArrayHasKey(
                $mode,
                Schedules::$dictionary['usage'],
                "Словарь 'usage' должен содержать режим '$mode'"
            );
        }
    }

    // -------------------------------------------------------------------------
    // SchedulesEntries — статические методы (без БД)
    // -------------------------------------------------------------------------

    public function testSchedulesEntriesTableName(): void
    {
        $this->assertSame('schedules_entries', SchedulesEntries::tableName());
    }

    /**
     * validateTime: корректные форматы.
     */
    public function testValidateTimeValid(): void
    {
        $this->assertTrue(SchedulesEntries::validateTime('08:00'));
        $this->assertTrue(SchedulesEntries::validateTime('00:00'));
        $this->assertTrue(SchedulesEntries::validateTime('23:59'));
        $this->assertTrue(SchedulesEntries::validateTime('12:30'));
    }

    /**
     * validateTime: некорректные форматы.
     *
     * Примечание: '8:00' считается валидным по коду (длина токена '8' = 1, что не > 2).
     * Код проверяет только strlen > 2, а не точно == 2.
     */
    public function testValidateTimeInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::validateTime(''));
        $this->assertFalse(SchedulesEntries::validateTime('24:00'));
        $this->assertFalse(SchedulesEntries::validateTime('12:60'));
        $this->assertFalse(SchedulesEntries::validateTime('123:00')); // часы > 2 символов
        $this->assertFalse(SchedulesEntries::validateTime('12:000')); // минуты > 2 символов
        $this->assertFalse(SchedulesEntries::validateTime('12'));     // нет разделителя
    }

    /**
     * validateSchedule: корректные форматы.
     */
    public function testValidateScheduleValid(): void
    {
        $this->assertTrue(SchedulesEntries::validateSchedule('08:00-17:00'));
        $this->assertTrue(SchedulesEntries::validateSchedule('00:00-23:59'));
        $this->assertTrue(SchedulesEntries::validateSchedule('22:00-06:00'));
        // С метаданными
        $this->assertTrue(SchedulesEntries::validateSchedule('08:00-17:00{"key":"val"}'));
    }

    /**
     * validateSchedule: некорректные форматы.
     */
    public function testValidateScheduleInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::validateSchedule(''));
        $this->assertFalse(SchedulesEntries::validateSchedule('08:00'));       // нет конца
        $this->assertFalse(SchedulesEntries::validateSchedule('08:00-17:00-18:00')); // три токена
    }

    /**
     * validateSchedules: корректные форматы.
     */
    public function testValidateSchedulesValid(): void
    {
        $this->assertTrue(SchedulesEntries::validateSchedules('-'));
        $this->assertTrue(SchedulesEntries::validateSchedules('08:00-17:00'));
        $this->assertTrue(SchedulesEntries::validateSchedules('08:00-12:00,13:00-17:00'));
    }

    /**
     * validateSchedules: некорректные форматы.
     */
    public function testValidateSchedulesInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::validateSchedules(''));
        $this->assertFalse(SchedulesEntries::validateSchedules('08:00'));
        $this->assertFalse(SchedulesEntries::validateSchedules('08:00-17:00,bad'));
    }

    /**
     * strTimestampToMinutes: конвертация HH:MM в минуты.
     */
    public function testStrTimestampToMinutes(): void
    {
        $this->assertSame(0,    SchedulesEntries::strTimestampToMinutes('00:00'));
        $this->assertSame(480,  SchedulesEntries::strTimestampToMinutes('08:00'));
        $this->assertSame(1020, SchedulesEntries::strTimestampToMinutes('17:00'));
        $this->assertSame(1439, SchedulesEntries::strTimestampToMinutes('23:59'));
    }

    /**
     * strTimestampToMinutes: с секундами.
     */
    public function testStrTimestampToMinutesWithSeconds(): void
    {
        $this->assertSame(480, SchedulesEntries::strTimestampToMinutes('08:00:30'));
    }

    /**
     * strTimestampToMinutes: некорректный формат → false.
     */
    public function testStrTimestampToMinutesInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::strTimestampToMinutes('invalid'));
        $this->assertFalse(SchedulesEntries::strTimestampToMinutes(''));
    }

    /**
     * intMinutesToStrTimestamp: конвертация минут в HH:MM.
     */
    public function testIntMinutesToStrTimestamp(): void
    {
        $this->assertSame('00:00', SchedulesEntries::intMinutesToStrTimestamp(0));
        $this->assertSame('08:00', SchedulesEntries::intMinutesToStrTimestamp(480));
        $this->assertSame('17:00', SchedulesEntries::intMinutesToStrTimestamp(1020));
        $this->assertSame('23:59', SchedulesEntries::intMinutesToStrTimestamp(1439));
    }

    /**
     * scheduleToMinuteInterval: конвертация HH:MM-HH:MM в [start, end].
     */
    public function testScheduleToMinuteInterval(): void
    {
        $result = SchedulesEntries::scheduleToMinuteInterval('08:00-17:00');
        $this->assertSame([480, 1020], $result);
    }

    public function testScheduleToMinuteIntervalInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::scheduleToMinuteInterval('invalid'));
        $this->assertFalse(SchedulesEntries::scheduleToMinuteInterval('08:00'));
    }

    /**
     * scheduleToMinuteIntervals: несколько периодов.
     */
    public function testScheduleToMinuteIntervals(): void
    {
        $result = SchedulesEntries::scheduleToMinuteIntervals('08:00-12:00,13:00-17:00');
        $this->assertCount(2, $result);
        $this->assertSame([480, 720], $result[0]);
        $this->assertSame([780, 1020], $result[1]);
    }

    /**
     * scheduleWithoutMetadata: удаляет JSON-метаданные.
     */
    public function testScheduleWithoutMetadata(): void
    {
        $this->assertSame(
            '08:00-17:00',
            SchedulesEntries::scheduleWithoutMetadata('08:00-17:00{"key":"value"}')
        );
        $this->assertSame(
            '08:00-17:00',
            SchedulesEntries::scheduleWithoutMetadata('08:00-17:00')
        );
    }

    /**
     * periodMetadata: извлекает JSON-метаданные.
     */
    public function testPeriodMetadata(): void
    {
        $this->assertSame(
            '{"key":"value"}',
            SchedulesEntries::periodMetadata('08:00-17:00{"key":"value"}')
        );
        $this->assertFalse(SchedulesEntries::periodMetadata('08:00-17:00'));
    }

    /**
     * minuteIntervalToSchedule: конвертация [start, end] в HH:MM-HH:MM.
     */
    public function testMinuteIntervalToSchedule(): void
    {
        $this->assertSame('08:00-17:00', SchedulesEntries::minuteIntervalToSchedule([480, 1020]));
        $this->assertSame('00:00-23:59', SchedulesEntries::minuteIntervalToSchedule([0, 1439]));
    }

    /**
     * minuteIntervalToSchedule: с метаданными.
     */
    public function testMinuteIntervalToScheduleWithMeta(): void
    {
        $result = SchedulesEntries::minuteIntervalToSchedule([480, 1020, 'meta' => '{"k":"v"}']);
        $this->assertSame('08:00-17:00{"k":"v"}', $result);
    }

    /**
     * scheduleMinuteIntervalFitDay: интервал не переходящий полночь — без изменений.
     */
    public function testScheduleMinuteIntervalFitDayNoOverhead(): void
    {
        $interval = [480, 1020]; // 08:00-17:00
        $result   = SchedulesEntries::scheduleMinuteIntervalFitDay($interval);
        $this->assertSame([480, 1020], $result);
    }

    /**
     * scheduleMinuteIntervalFitDay: интервал переходящий полночь [22:00-06:00] → [22:00-23:59].
     */
    public function testScheduleMinuteIntervalFitDayOvernight(): void
    {
        $interval = [1320, 360]; // 22:00-06:00
        $result   = SchedulesEntries::scheduleMinuteIntervalFitDay($interval);
        $this->assertSame(1320, $result[0]);
        $this->assertSame(24 * 60 - 1, $result[1]); // 1439
    }

    /**
     * scheduleMinuteIntervalOverheadDay: интервал не переходящий полночь → null.
     */
    public function testScheduleMinuteIntervalOverheadDayNoOverhead(): void
    {
        $interval = [480, 1020];
        $this->assertNull(SchedulesEntries::scheduleMinuteIntervalOverheadDay($interval));
    }

    /**
     * scheduleMinuteIntervalOverheadDay: интервал переходящий полночь [22:00-06:00] → [00:00-06:00].
     */
    public function testScheduleMinuteIntervalOverheadDayOvernight(): void
    {
        $interval = [1320, 360]; // 22:00-06:00
        $result   = SchedulesEntries::scheduleMinuteIntervalOverheadDay($interval);
        $this->assertSame(0, $result[0]);
        $this->assertSame(360, $result[1]);
    }

    /**
     * scheduleExToMinuteInterval: с метаданными.
     */
    public function testScheduleExToMinuteIntervalWithMeta(): void
    {
        $result = SchedulesEntries::scheduleExToMinuteInterval('08:00-17:00{"k":"v"}');
        $this->assertSame(480, $result[0]);
        $this->assertSame(1020, $result[1]);
        $this->assertSame('{"k":"v"}', $result['meta']);
    }

    /**
     * scheduleExToMinuteInterval: без метаданных → meta=false.
     */
    public function testScheduleExToMinuteIntervalNoMeta(): void
    {
        $result = SchedulesEntries::scheduleExToMinuteInterval('08:00-17:00');
        $this->assertSame(480, $result[0]);
        $this->assertSame(1020, $result[1]);
        $this->assertFalse($result['meta']);
    }

    /**
     * scheduleExToMinuteInterval: некорректный формат → false.
     */
    public function testScheduleExToMinuteIntervalInvalid(): void
    {
        $this->assertFalse(SchedulesEntries::scheduleExToMinuteInterval('invalid'));
    }

    /**
     * $days содержит все ожидаемые ключи.
     */
    public function testDaysArrayKeys(): void
    {
        $expected = ['def', '1', '2', '3', '4', '5', '6', '7'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, SchedulesEntries::$days, "Массив \$days должен содержать ключ '$key'");
        }
    }

    /**
     * $isWorkComment содержит оба режима.
     */
    public function testIsWorkCommentStructure(): void
    {
        $this->assertArrayHasKey('default', SchedulesEntries::$isWorkComment);
        $this->assertArrayHasKey('acl', SchedulesEntries::$isWorkComment);
        $this->assertArrayHasKey(0, SchedulesEntries::$isWorkComment['default']);
        $this->assertArrayHasKey(1, SchedulesEntries::$isWorkComment['default']);
    }
}
