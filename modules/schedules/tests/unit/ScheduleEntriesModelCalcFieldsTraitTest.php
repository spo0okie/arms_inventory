<?php

namespace app\modules\schedules\tests\unit;

use app\modules\schedules\models\traits\ScheduleEntriesModelCalcFieldsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для ScheduleEntriesModelCalcFieldsTrait.
 *
 * Тестируем методы трейта через анонимный класс-заглушку.
 * Методы, требующие реальной модели SchedulesEntries (с БД), тестируются
 * через статические методы SchedulesEntries напрямую.
 */
class ScheduleEntriesModelCalcFieldsTraitTest extends TestCase
{
    /**
     * Создаёт минимальный объект, использующий трейт.
     *
     * @param array $props
     * @return object
     */
    private function makeStub(array $props = []): object
    {
        $stub = new class {
            use ScheduleEntriesModelCalcFieldsTrait;

            // Свойства, которые читает трейт
            public $date       = null;
            public $date_end   = null;
            public $schedule   = null;
            public $is_period  = 0;
            public $is_work    = 1;
            public $master     = null;
            public $isAclCache = null;
            public $requestedWeekDay = null;
            public $requestedDate    = null;
            public $previousDateEntry = null;
        };

        foreach ($props as $key => $value) {
            $stub->$key = $value;
        }

        return $stub;
    }

    // -------------------------------------------------------------------------
    // getDay / getDayFor
    // -------------------------------------------------------------------------

    /**
     * Числовой день недели → возвращает название из словаря.
     */
    public function testGetDayReturnsWeekdayName(): void
    {
        // Подключаем SchedulesEntries::$days через статическое свойство
        // Трейт обращается к SchedulesEntries::$days напрямую
        $stub = $this->makeStub(['date' => '1']);
        // '1' → 'Пн' согласно SchedulesEntries::$days
        $this->assertSame('Пн', $stub->getDay());
    }

    public function testGetDayReturnsDefaultLabel(): void
    {
        $stub = $this->makeStub(['date' => 'def']);
        $this->assertSame('По умолч.', $stub->getDay());
    }

    /**
     * Дата в формате YYYY-MM-DD — возвращается как есть (нет в словаре).
     */
    public function testGetDayReturnsDateAsIs(): void
    {
        $stub = $this->makeStub(['date' => '2024-06-15']);
        $this->assertSame('2024-06-15', $stub->getDay());
    }

    public function testGetDayForReturnsWeekdayPreposition(): void
    {
        $stub = $this->makeStub(['date' => '5']);
        // '5' → 'на пт' согласно SchedulesEntries::$daysFor
        $this->assertSame('на пт', $stub->getDayFor());
    }

    public function testGetDayForReturnsDateAsIs(): void
    {
        $stub = $this->makeStub(['date' => '2024-06-15']);
        $this->assertSame('2024-06-15', $stub->getDayFor());
    }

    // -------------------------------------------------------------------------
    // getSchedulePeriods
    // -------------------------------------------------------------------------

    public function testGetSchedulePeriodsEmptyWhenDash(): void
    {
        $stub = $this->makeStub(['schedule' => '-']);
        $this->assertSame([], $stub->getSchedulePeriods());
    }

    public function testGetSchedulePeriodsReturnsSinglePeriod(): void
    {
        $stub = $this->makeStub(['schedule' => '08:00-17:00']);
        $this->assertSame(['08:00-17:00'], $stub->getSchedulePeriods());
    }

    public function testGetSchedulePeriodsReturnsMultiplePeriods(): void
    {
        $stub = $this->makeStub(['schedule' => '08:00-12:00,13:00-17:00']);
        $periods = $stub->getSchedulePeriods();
        $this->assertCount(2, $periods);
        $this->assertSame('08:00-12:00', $periods[0]);
        $this->assertSame('13:00-17:00', $periods[1]);
    }

    // -------------------------------------------------------------------------
    // getIsAcl
    // -------------------------------------------------------------------------

    public function testGetIsAclFalseWhenNoMaster(): void
    {
        $stub = $this->makeStub(['master' => null]);
        $this->assertFalse($stub->getIsAcl());
    }

    public function testGetIsAclFalseWhenMasterIsNotAcl(): void
    {
        $masterStub = new \stdClass();
        $masterStub->isAcl = false;
        $stub = $this->makeStub(['master' => $masterStub]);
        $this->assertFalse($stub->getIsAcl());
    }

    public function testGetIsAclTrueWhenMasterIsAcl(): void
    {
        $masterStub = new \stdClass();
        $masterStub->isAcl = true;
        $stub = $this->makeStub(['master' => $masterStub]);
        $this->assertTrue($stub->getIsAcl());
    }

    /**
     * Кэш: повторный вызов не должен обращаться к master повторно.
     */
    public function testGetIsAclCached(): void
    {
        $masterStub = new \stdClass();
        $masterStub->isAcl = true;
        $stub = $this->makeStub(['master' => $masterStub]);

        $first  = $stub->getIsAcl();
        // Меняем master — кэш должен вернуть старое значение
        $stub->master = null;
        $second = $stub->getIsAcl();

        $this->assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // getPreviousWeekDay
    // -------------------------------------------------------------------------

    /**
     * Если date='def' — возвращает сам объект.
     */
    public function testGetPreviousWeekDayReturnsSelfForDef(): void
    {
        $stub = $this->makeStub(['date' => 'def', 'requestedWeekDay' => null]);
        $this->assertSame($stub, $stub->getPreviousWeekDay());
    }

    /**
     * Если нет master — возвращает null.
     */
    public function testGetPreviousWeekDayNullWhenNoMaster(): void
    {
        $stub = $this->makeStub(['date' => '3', 'master' => null]);
        $this->assertNull($stub->getPreviousWeekDay());
    }

    // -------------------------------------------------------------------------
    // getPeriodSchedule
    // -------------------------------------------------------------------------

    /**
     * Если is_period=0 — возвращает null.
     */
    public function testGetPeriodScheduleNullWhenNotPeriod(): void
    {
        $stub = $this->makeStub(['is_period' => 0]);
        $this->assertNull($stub->getPeriodSchedule());
    }

    /**
     * Период в один день — формат 'YYYY-MM-DD HH:MM-HH:MM'.
     */
    public function testGetPeriodScheduleSameDay(): void
    {
        $stub = $this->makeStub([
            'is_period' => 1,
            'date'      => '2024-06-15 08:00:00',
            'date_end'  => '2024-06-15 17:00:00',
        ]);
        $result = $stub->getPeriodSchedule();
        $this->assertStringContainsString('2024-06-15', $result);
        $this->assertStringContainsString('08:00', $result);
        $this->assertStringContainsString('17:00', $result);
    }

    /**
     * Период на несколько дней — формат 'YYYY-MM-DD - YYYY-MM-DD'.
     */
    public function testGetPeriodScheduleMultiDay(): void
    {
        $stub = $this->makeStub([
            'is_period' => 1,
            'date'      => '2024-06-15 00:00:00',
            'date_end'  => '2024-06-20 23:59:59',
        ]);
        $result = $stub->getPeriodSchedule();
        $this->assertStringContainsString('2024-06-15', $result);
        $this->assertStringContainsString('2024-06-20', $result);
    }

    /**
     * Период без начала — 'нет начала'.
     */
    public function testGetPeriodScheduleNullStart(): void
    {
        $stub = $this->makeStub([
            'is_period' => 1,
            'date'      => null,
            'date_end'  => '2024-06-20 23:59:59',
        ]);
        $result = $stub->getPeriodSchedule();
        $this->assertStringContainsString('нет начала', $result);
    }

    /**
     * Период без конца — 'нет конца'.
     */
    public function testGetPeriodScheduleNullEnd(): void
    {
        $stub = $this->makeStub([
            'is_period' => 1,
            'date'      => '2024-06-15 00:00:00',
            'date_end'  => null,
        ]);
        $result = $stub->getPeriodSchedule();
        $this->assertStringContainsString('нет конца', $result);
    }
}
