<?php

namespace app\modules\schedules\tests\unit;

use app\modules\schedules\models\traits\SchedulesModelCalcFieldsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для SchedulesModelCalcFieldsTrait.
 *
 * Используем анонимный класс-заглушку вместо реальной модели Schedules,
 * чтобы не требовать БД и полного поднятия Yii2.
 */
class SchedulesModelCalcFieldsTraitTest extends TestCase
{
    /**
     * Создаёт минимальный объект, использующий трейт.
     *
     * Трейт использует магические геттеры вида $this->someProperty,
     * которые в реальной модели разрешаются через __get() ActiveRecord.
     * Здесь реализуем минимальный __get для поддержки вычисляемых свойств трейта.
     *
     * @param array $props Свойства, которые нужно установить на объекте.
     * @return object
     */
    private function makeStub(array $props = []): object
    {
        $stub = new class {
            use SchedulesModelCalcFieldsTrait;

            // Минимальный набор свойств, которые читает трейт
            public $attrsCache = [];
            public $start_date = null;
            public $end_date   = null;
            public $override_id = null;
            public $id         = null;
            public $parent_id  = null;
            public $parent     = null;
            public $overriding = null;
            public $overrides  = [];
            public $acls       = [];
            public $providingServices = [];
            public $supportServices   = [];
            public $maintenanceJobs   = [];
            public $entries    = [];

            /**
             * Эмулирует магические геттеры ActiveRecord:
             * $this->someProperty → $this->getSomeProperty()
             */
            public function __get(string $name): mixed
            {
                $getter = 'get' . ucfirst($name);
                if (method_exists($this, $getter)) {
                    return $this->$getter();
                }
                return null;
            }
        };

        foreach ($props as $key => $value) {
            $stub->$key = $value;
        }

        return $stub;
    }

    // -------------------------------------------------------------------------
    // getIsOverride
    // -------------------------------------------------------------------------

    public function testGetIsOverrideFalseWhenNoOverrideId(): void
    {
        $stub = $this->makeStub(['override_id' => null]);
        $this->assertFalse($stub->getIsOverride());
    }

    public function testGetIsOverrideTrueWhenOverrideIdSet(): void
    {
        $stub = $this->makeStub(['override_id' => 42]);
        $this->assertTrue($stub->getIsOverride());
    }

    // -------------------------------------------------------------------------
    // getBaseId
    // -------------------------------------------------------------------------

    public function testGetBaseIdReturnsOwnIdWhenNotOverride(): void
    {
        $stub = $this->makeStub(['id' => 10, 'override_id' => null]);
        $this->assertSame(10, $stub->getBaseId());
    }

    public function testGetBaseIdReturnsOverrideIdWhenIsOverride(): void
    {
        $stub = $this->makeStub(['id' => 10, 'override_id' => 99]);
        $this->assertSame(99, $stub->getBaseId());
    }

    // -------------------------------------------------------------------------
    // getStartUnixTime / getEndUnixTime
    // -------------------------------------------------------------------------

    public function testGetStartUnixTimeNullWhenNullDate(): void
    {
        $stub = $this->makeStub(['start_date' => null]);
        $this->assertNull($stub->getStartUnixTime());
    }

    public function testGetStartUnixTimeReturnsTimestamp(): void
    {
        $stub = $this->makeStub(['start_date' => '2024-01-15']);
        $expected = strtotime('2024-01-15');
        $this->assertSame($expected, $stub->getStartUnixTime());
    }

    public function testGetEndUnixTimeNullWhenNullDate(): void
    {
        $stub = $this->makeStub(['end_date' => null]);
        $this->assertNull($stub->getEndUnixTime());
    }

    public function testGetEndUnixTimeReturnsTimestamp(): void
    {
        $stub = $this->makeStub(['end_date' => '2024-12-31']);
        $expected = strtotime('2024-12-31');
        $this->assertSame($expected, $stub->getEndUnixTime());
    }

    /**
     * Кэш: повторный вызов должен вернуть то же значение.
     */
    public function testGetStartUnixTimeCached(): void
    {
        $stub = $this->makeStub(['start_date' => '2024-06-01']);
        $first  = $stub->getStartUnixTime();
        $second = $stub->getStartUnixTime();
        $this->assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // endsBeforeDate
    // -------------------------------------------------------------------------

    public function testEndsBeforeDateFalseWhenNoEndDate(): void
    {
        $stub = $this->makeStub(['end_date' => null]);
        $this->assertFalse($stub->endsBeforeDate('2024-06-01'));
    }

    public function testEndsBeforeDateTrueWhenEndBeforeDate(): void
    {
        $stub = $this->makeStub(['end_date' => '2024-01-01']);
        $this->assertTrue($stub->endsBeforeDate('2024-06-01'));
    }

    public function testEndsBeforeDateFalseWhenEndAfterDate(): void
    {
        $stub = $this->makeStub(['end_date' => '2024-12-31']);
        $this->assertFalse($stub->endsBeforeDate('2024-06-01'));
    }

    /**
     * Принимает unixtime вместо строки.
     */
    public function testEndsBeforeDateAcceptsUnixtime(): void
    {
        $stub = $this->makeStub(['end_date' => '2024-01-01']);
        $this->assertTrue($stub->endsBeforeDate(strtotime('2024-06-01')));
    }

    // -------------------------------------------------------------------------
    // startsAfterDate
    // -------------------------------------------------------------------------

    public function testStartsAfterDateFalseWhenNoStartDate(): void
    {
        $stub = $this->makeStub(['start_date' => null]);
        $this->assertFalse($stub->startsAfterDate('2024-06-01'));
    }

    public function testStartsAfterDateTrueWhenStartAfterDate(): void
    {
        $stub = $this->makeStub(['start_date' => '2024-12-01']);
        $this->assertTrue($stub->startsAfterDate('2024-06-01'));
    }

    public function testStartsAfterDateFalseWhenStartBeforeDate(): void
    {
        $stub = $this->makeStub(['start_date' => '2024-01-01']);
        $this->assertFalse($stub->startsAfterDate('2024-06-01'));
    }

    // -------------------------------------------------------------------------
    // matchDate
    // -------------------------------------------------------------------------

    /**
     * Расписание без границ перекрывает любую дату.
     */
    public function testMatchDateTrueWhenNoBoundaries(): void
    {
        $stub = $this->makeStub(['start_date' => null, 'end_date' => null]);
        $this->assertTrue($stub->matchDate('2024-06-01'));
    }

    /**
     * Дата до начала расписания — не перекрывает.
     */
    public function testMatchDateFalseWhenBeforeStart(): void
    {
        $stub = $this->makeStub(['start_date' => '2024-07-01', 'end_date' => null]);
        $this->assertFalse($stub->matchDate('2024-06-01'));
    }

    /**
     * Дата после конца расписания — не перекрывает.
     */
    public function testMatchDateFalseWhenAfterEnd(): void
    {
        $stub = $this->makeStub(['start_date' => null, 'end_date' => '2024-05-01']);
        $this->assertFalse($stub->matchDate('2024-06-01'));
    }

    /**
     * Дата внутри границ — перекрывает.
     */
    public function testMatchDateTrueWhenInsideBoundaries(): void
    {
        $stub = $this->makeStub([
            'start_date' => '2024-01-01',
            'end_date'   => '2024-12-31',
        ]);
        $this->assertTrue($stub->matchDate('2024-06-01'));
    }

    // -------------------------------------------------------------------------
    // getParentsChain
    // -------------------------------------------------------------------------

    /**
     * Расписание без родителя — цепочка содержит только себя.
     */
    public function testGetParentsChainSingleNode(): void
    {
        $stub = $this->makeStub(['id' => 5, 'parent_id' => null]);
        $chain = $stub->getParentsChain();
        $this->assertArrayHasKey(5, $chain);
        $this->assertCount(1, $chain);
    }

    /**
     * Защита от циклических ссылок: если объект уже в цепочке, рекурсия останавливается.
     */
    public function testGetParentsChainCircularProtection(): void
    {
        $stub = $this->makeStub(['id' => 1, 'parent_id' => null]);
        // Передаём цепочку, в которой уже есть этот объект
        $chain = $stub->getParentsChain([1 => $stub]);
        // Должна вернуться та же цепочка без изменений
        $this->assertCount(1, $chain);
    }

    // -------------------------------------------------------------------------
    // getIsAcl
    // -------------------------------------------------------------------------

    public function testGetIsAclFalseWhenNoAcls(): void
    {
        $stub = $this->makeStub(['acls' => []]);
        $this->assertFalse($stub->getIsAcl());
    }

    public function testGetIsAclTrueWhenAclsPresent(): void
    {
        // Создаём заглушку ACL
        $aclStub = new \stdClass();
        $stub = $this->makeStub(['acls' => [$aclStub]]);
        $this->assertTrue($stub->getIsAcl());
    }

    public function testGetIsAclFalseWhenAclsNotArray(): void
    {
        $stub = $this->makeStub(['acls' => null]);
        $this->assertFalse($stub->getIsAcl());
    }
}
