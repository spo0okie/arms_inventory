<?php

namespace app\modules\schedules\tests\unit;

use app\modules\schedules\helpers\TimeIntervalsHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для TimeIntervalsHelper.
 *
 * Все тесты — чистая математика, без БД и Yii2.
 */
class TimeIntervalsHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // dayMinutesOverheadFix / dayMinutesOverheadHumanize
    // -------------------------------------------------------------------------

    /**
     * Интервал, не переходящий полночь, не должен меняться.
     */
    public function testDayMinutesOverheadFixNoChange(): void
    {
        $interval = [480, 1020]; // 08:00 - 17:00
        $this->assertSame([480, 1020], TimeIntervalsHelper::dayMinutesOverheadFix($interval));
    }

    /**
     * Интервал [22:00-06:00] должен стать [1320, 1800] (1320+480=1800).
     */
    public function testDayMinutesOverheadFixOvernight(): void
    {
        $interval = [1320, 360]; // 22:00 - 06:00
        $result = TimeIntervalsHelper::dayMinutesOverheadFix($interval);
        $this->assertSame(1320, $result[0]);
        $this->assertSame(360 + 1440, $result[1]); // 1800
    }

    /**
     * Граничный случай: начало == конец (нулевой интервал).
     */
    public function testDayMinutesOverheadFixZeroInterval(): void
    {
        $interval = [600, 600];
        $this->assertSame([600, 600], TimeIntervalsHelper::dayMinutesOverheadFix($interval));
    }

    /**
     * Humanize должен вернуть математически корректный интервал к читаемому виду.
     */
    public function testDayMinutesOverheadHumanize(): void
    {
        $interval = [1320, 1800]; // математически корректный
        $result = TimeIntervalsHelper::dayMinutesOverheadHumanize($interval);
        $this->assertSame(1320, $result[0]);
        $this->assertSame(360, $result[1]); // 1800 - 1440 = 360
    }

    /**
     * Humanize не должен трогать интервал, не превышающий 1440.
     */
    public function testDayMinutesOverheadHumanizeNoChange(): void
    {
        $interval = [480, 1020];
        $this->assertSame([480, 1020], TimeIntervalsHelper::dayMinutesOverheadHumanize($interval));
    }

    /**
     * FixAll / HumanizeAll применяют операцию ко всем элементам массива.
     */
    public function testDayMinutesOverheadFixAll(): void
    {
        $intervals = [
            [480, 1020],   // 08:00-17:00 — без изменений
            [1320, 360],   // 22:00-06:00 — должен исправиться
        ];
        $result = TimeIntervalsHelper::dayMinutesOverheadFixAll($intervals);
        $this->assertSame([480, 1020], $result[0]);
        $this->assertSame([1320, 1800], $result[1]);
    }

    public function testDayMinutesOverheadHumanizeAll(): void
    {
        $intervals = [
            [480, 1020],
            [1320, 1800],
        ];
        $result = TimeIntervalsHelper::dayMinutesOverheadHumanizeAll($intervals);
        $this->assertSame([480, 1020], $result[0]);
        $this->assertSame([1320, 360], $result[1]);
    }

    // -------------------------------------------------------------------------
    // intervalCut
    // -------------------------------------------------------------------------

    /**
     * Интервал полностью внутри диапазона — не меняется.
     */
    public function testIntervalCutInsideRange(): void
    {
        $interval = [100, 200];
        $range    = [0, 300];
        $this->assertSame([100, 200], TimeIntervalsHelper::intervalCut($interval, $range));
    }

    /**
     * Интервал выходит за левую границу — обрезается слева.
     */
    public function testIntervalCutLeftOverflow(): void
    {
        $interval = [50, 200];
        $range    = [100, 300];
        $result   = TimeIntervalsHelper::intervalCut($interval, $range);
        $this->assertSame(100, $result[0]);
        $this->assertSame(200, $result[1]);
    }

    /**
     * Интервал выходит за правую границу — обрезается справа.
     */
    public function testIntervalCutRightOverflow(): void
    {
        $interval = [100, 400];
        $range    = [0, 300];
        $result   = TimeIntervalsHelper::intervalCut($interval, $range);
        $this->assertSame(100, $result[0]);
        $this->assertSame(300, $result[1]);
    }

    /**
     * NULL-граница означает открытый интервал — должна заменяться границей диапазона.
     */
    public function testIntervalCutNullBoundaries(): void
    {
        $interval = [null, null];
        $range    = [100, 500];
        $result   = TimeIntervalsHelper::intervalCut($interval, $range);
        $this->assertSame(100, $result[0]);
        $this->assertSame(500, $result[1]);
    }

    // -------------------------------------------------------------------------
    // intervalCheck
    // -------------------------------------------------------------------------

    /**
     * Значение внутри интервала.
     */
    public function testIntervalCheckInside(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalCheck([100, 200], 150));
    }

    /**
     * Значение на левой границе.
     */
    public function testIntervalCheckLeftBoundary(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalCheck([100, 200], 100));
    }

    /**
     * Значение на правой границе.
     */
    public function testIntervalCheckRightBoundary(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalCheck([100, 200], 200));
    }

    /**
     * Значение левее интервала.
     */
    public function testIntervalCheckBeforeInterval(): void
    {
        $this->assertFalse(TimeIntervalsHelper::intervalCheck([100, 200], 50));
    }

    /**
     * Значение правее интервала.
     */
    public function testIntervalCheckAfterInterval(): void
    {
        $this->assertFalse(TimeIntervalsHelper::intervalCheck([100, 200], 250));
    }

    /**
     * false вместо интервала — всегда false.
     */
    public function testIntervalCheckFalseInterval(): void
    {
        $this->assertFalse(TimeIntervalsHelper::intervalCheck(false, 150));
    }

    // -------------------------------------------------------------------------
    // intervalIntersect
    // -------------------------------------------------------------------------

    /**
     * Явное пересечение.
     */
    public function testIntervalIntersectOverlap(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalIntersect([100, 200], [150, 300]));
    }

    /**
     * Один внутри другого.
     */
    public function testIntervalIntersectContained(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalIntersect([100, 300], [150, 200]));
    }

    /**
     * Интервалы не пересекаются.
     */
    public function testIntervalIntersectNoOverlap(): void
    {
        $this->assertFalse(TimeIntervalsHelper::intervalIntersect([100, 200], [300, 400]));
    }

    /**
     * Касание без флага touch — не пересечение.
     */
    public function testIntervalIntersectTouchWithoutFlag(): void
    {
        // [100,200] и [201,300] — касаются (200+1=201)
        $this->assertFalse(TimeIntervalsHelper::intervalIntersect([100, 200], [201, 300], false));
    }

    /**
     * Касание с флагом touch — пересечение.
     */
    public function testIntervalIntersectTouchWithFlag(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalIntersect([100, 200], [201, 300], true));
    }

    /**
     * NULL-начало второго интервала (луч влево) — всегда пересекается.
     */
    public function testIntervalIntersectNullStart(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalIntersect([100, 200], [null, 300]));
    }

    /**
     * NULL-конец первого интервала (луч вправо) — всегда пересекается.
     */
    public function testIntervalIntersectNullEnd(): void
    {
        $this->assertTrue(TimeIntervalsHelper::intervalIntersect([100, null], [200, 300]));
    }

    // -------------------------------------------------------------------------
    // intervalsCompare
    // -------------------------------------------------------------------------

    /**
     * Одинаковые интервалы → 0.
     */
    public function testIntervalsCompareEqual(): void
    {
        $this->assertSame(0, TimeIntervalsHelper::intervalsCompare([100, 200], [100, 200]));
    }

    /**
     * Первый начинается позже → 1.
     */
    public function testIntervalsCompareFirstLater(): void
    {
        $this->assertSame(1, TimeIntervalsHelper::intervalsCompare([200, 300], [100, 300]));
    }

    /**
     * Первый начинается раньше → -1.
     */
    public function testIntervalsCompareFirstEarlier(): void
    {
        $this->assertSame(-1, TimeIntervalsHelper::intervalsCompare([100, 300], [200, 300]));
    }

    /**
     * Одинаковое начало, первый заканчивается позже → 1.
     */
    public function testIntervalsCompareSameStartFirstEndsLater(): void
    {
        $this->assertSame(1, TimeIntervalsHelper::intervalsCompare([100, 300], [100, 200]));
    }

    /**
     * Одинаковое начало, первый — луч вправо (null конец) → 1.
     */
    public function testIntervalsCompareSameStartFirstIsRay(): void
    {
        $this->assertSame(1, TimeIntervalsHelper::intervalsCompare([100, null], [100, 200]));
    }

    // -------------------------------------------------------------------------
    // intervalsSort
    // -------------------------------------------------------------------------

    public function testIntervalsSort(): void
    {
        $intervals = [
            [300, 400],
            [100, 200],
            [200, 300],
        ];
        TimeIntervalsHelper::intervalsSort($intervals);
        $this->assertSame([100, 200], $intervals[0]);
        $this->assertSame([200, 300], $intervals[1]);
        $this->assertSame([300, 400], $intervals[2]);
    }

    // -------------------------------------------------------------------------
    // intervalSubtraction
    // -------------------------------------------------------------------------

    /**
     * Вычитаемое полностью перекрывает уменьшаемое → пустой массив.
     */
    public function testIntervalSubtractionFullOverlap(): void
    {
        $result = TimeIntervalsHelper::intervalSubtraction([100, 200], [50, 300]);
        $this->assertSame([], $result);
    }

    /**
     * Вычитаемое не пересекается → уменьшаемое не меняется.
     */
    public function testIntervalSubtractionNoOverlap(): void
    {
        $A = [100, 200];
        $B = [300, 400];
        $result = TimeIntervalsHelper::intervalSubtraction($A, $B);
        $this->assertSame($A, $result);
    }

    /**
     * Вычитаемое откусывает левый кусок.
     */
    public function testIntervalSubtractionLeftCut(): void
    {
        // A=[100,300], B=[50,150] → остаток [150,300]
        $result = TimeIntervalsHelper::intervalSubtraction([100, 300], [50, 150]);
        $this->assertCount(1, $result);
        $this->assertSame(150, $result[0][0]);
        $this->assertSame(300, $result[0][1]);
    }

    /**
     * Вычитаемое откусывает правый кусок.
     */
    public function testIntervalSubtractionRightCut(): void
    {
        // A=[100,300], B=[200,400] → остаток [100,200]
        $result = TimeIntervalsHelper::intervalSubtraction([100, 300], [200, 400]);
        $this->assertCount(1, $result);
        $this->assertSame(100, $result[0][0]);
        $this->assertSame(200, $result[0][1]);
    }

    /**
     * Вычитаемое внутри уменьшаемого → два куска.
     */
    public function testIntervalSubtractionMiddleCut(): void
    {
        // A=[100,400], B=[200,300] → [100,200] и [300,400]
        $result = TimeIntervalsHelper::intervalSubtraction([100, 400], [200, 300]);
        $this->assertCount(2, $result);
        $this->assertSame(100, $result[0][0]);
        $this->assertSame(200, $result[0][1]);
        $this->assertSame(300, $result[1][0]);
        $this->assertSame(400, $result[1][1]);
    }

    // -------------------------------------------------------------------------
    // intervalsSubtraction (массив из массива)
    // -------------------------------------------------------------------------

    public function testIntervalsSubtractionMultiple(): void
    {
        $minuend    = [[100, 500]];
        $subtrahend = [[150, 200], [300, 350]];
        $result     = TimeIntervalsHelper::intervalsSubtraction($minuend, $subtrahend);
        // Фильтруем только реальные интервалы (массивы) из результата
        // (intervalSubtraction может вернуть $A напрямую, что при array_merge даёт числа)
        $intervals = array_values(array_filter($result, 'is_array'));
        $this->assertCount(2, $intervals);
        // Проверяем что оба интервала корректны
        $this->assertSame(200, $intervals[0][0]);
        $this->assertSame(300, $intervals[0][1]);
        $this->assertSame(350, $intervals[1][0]);
        $this->assertSame(500, $intervals[1][1]);
    }

    public function testIntervalsSubtractionEmptySubtrahend(): void
    {
        $minuend = [[100, 200], [300, 400]];
        $result  = TimeIntervalsHelper::intervalsSubtraction($minuend, []);
        $this->assertSame($minuend, $result);
    }

    // -------------------------------------------------------------------------
    // intervalMerge
    // -------------------------------------------------------------------------

    /**
     * Пересекающиеся интервалы склеиваются.
     */
    public function testIntervalMergeOverlapping(): void
    {
        $intervals = [[100, 200], [150, 300]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals);
        $this->assertCount(1, $result);
        $this->assertSame(100, $result[0][0]);
        $this->assertSame(300, $result[0][1]);
    }

    /**
     * Непересекающиеся интервалы не склеиваются.
     */
    public function testIntervalMergeNonOverlapping(): void
    {
        $intervals = [[100, 200], [300, 400]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals);
        $this->assertCount(2, $result);
    }

    /**
     * Касающиеся интервалы склеиваются при touch=true.
     */
    public function testIntervalMergeTouchTrue(): void
    {
        $intervals = [[100, 200], [201, 300]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals, true);
        $this->assertCount(1, $result);
        $this->assertSame(100, $result[0][0]);
        $this->assertSame(300, $result[0][1]);
    }

    /**
     * Касающиеся интервалы НЕ склеиваются при touch=false.
     */
    public function testIntervalMergeTouchFalse(): void
    {
        $intervals = [[100, 200], [201, 300]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals, false);
        $this->assertCount(2, $result);
    }

    /**
     * Один интервал — возвращается как есть.
     */
    public function testIntervalMergeSingleInterval(): void
    {
        $intervals = [[100, 200]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals);
        $this->assertCount(1, $result);
        $this->assertSame([100, 200], $result[0]);
    }

    /**
     * Пустой массив — возвращается пустой массив.
     */
    public function testIntervalMergeEmpty(): void
    {
        $this->assertSame([], TimeIntervalsHelper::intervalMerge([]));
    }

    /**
     * Три пересекающихся интервала склеиваются в один.
     */
    public function testIntervalMergeThreeOverlapping(): void
    {
        $intervals = [[100, 200], [150, 250], [200, 350]];
        $result    = TimeIntervalsHelper::intervalMerge($intervals);
        $this->assertCount(1, $result);
        $this->assertSame(100, $result[0][0]);
        $this->assertSame(350, $result[0][1]);
    }

    // -------------------------------------------------------------------------
    // intervalTile
    // -------------------------------------------------------------------------

    /**
     * Непересекающиеся интервалы не меняются.
     */
    public function testIntervalTileNonOverlapping(): void
    {
        $intervals = [[100, 200], [300, 400]];
        $result    = TimeIntervalsHelper::intervalTile($intervals);
        $this->assertCount(2, $result);
    }

    /**
     * Пересекающиеся интервалы укладываются стык в стык.
     * [100,300] и [200,400] → [100,199] и [200,400]
     */
    public function testIntervalTileOverlapping(): void
    {
        $intervals = [[100, 300], [200, 400]];
        $result    = TimeIntervalsHelper::intervalTile($intervals);
        // После укладки должно быть 2 интервала без пересечений
        $this->assertCount(2, $result);
        // Проверяем что они не пересекаются
        $this->assertFalse(
            TimeIntervalsHelper::intervalIntersect($result[0], $result[1]),
            'После tile интервалы не должны пересекаться'
        );
    }

    /**
     * Идентичные интервалы — один удаляется.
     */
    public function testIntervalTileIdentical(): void
    {
        $intervals = [[100, 200], [100, 200]];
        $result    = TimeIntervalsHelper::intervalTile($intervals);
        $this->assertCount(1, $result);
    }

    /**
     * Вырожденный интервал (длина 0) удаляется.
     */
    public function testIntervalTileZeroLength(): void
    {
        $intervals = [[100, 100], [200, 300]];
        $result    = TimeIntervalsHelper::intervalTile($intervals);
        // Вырожденный [100,100] должен быть удалён
        $this->assertCount(1, $result);
        // Переиндексируем для надёжного доступа по ключу 0
        $result = array_values($result);
        $this->assertSame(200, $result[0][0]);
        $this->assertSame(300, $result[0][1]);
    }
}
