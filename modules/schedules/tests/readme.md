# Тесты модуля `schedules`

Карта unit-тестов модуля. Тесты разнесены по нескольким локациям, что обусловлено
исторической эволюцией модуля и разделением «чистых» unit-тестов модуля от тестов,
которым нужна реальная БД.

> Дата последнего обновления документа — см. git history. Если правите тесты,
> добавляйте/удаляйте записи в этом файле.

---

## Где что лежит

| Локация | Изоляция | Что покрывает |
| ------- | -------- | ------------- |
| [`modules/schedules/tests/unit/`](unit/) | без БД (юнит-моки) | базовая алгоритмика моделей, helper'ов, трейтов |
| [`tests/unit/modules/schedules/`](../../../tests/unit/modules/schedules/) | с БД (codecept Yii fixtures) | компиляция, валидации, lifecycle, рантайм |
| [`modules/schedules/compile/lib/js/demo.test.js`](../compile/lib/js/demo.test.js) | Jest (Node) | JS-рантайм `ScheduleRuntime` |

### Запуск

```bash
# PHP unit (вся проекта, включая модуль)
php vendor/bin/codecept run unit

# Только тесты модуля schedules
php vendor/bin/codecept run unit tests/unit/modules/schedules

# JS-рантайм
cd modules/schedules/compile/lib/js && npx jest
```

---

## Реализовано (PHP)

### `tests/unit/modules/schedules/SchedulesCompilerTest.php` — компиляция в JSON

Покрывает [`SchedulesCompiler`](../compile/SchedulesCompiler.php) — превращение модели расписания (с предками и overrides) в плоский `compiled_json`.

| Тест | Что проверяет |
| ---- | ------------- |
| `testParseSimpleSchedule` | `'08:00-17:00'` → `[[480, 1020, []]]` |
| `testParseMultipleIntervals` | `'08:00-12:00,13:00-17:00'` → два интервала |
| `testParseDashReturnsEmpty` | `'-'` → `[]` (выходной) |
| `testParseEmptyReturnsEmpty` | пустая строка → `[]` |
| `testParseSchedulePreservesMeta` | `'08:00-12:00{duty:Иванов}'` → meta попадает в третий элемент |
| `testParseScheduleSortsByStart` | интервалы отсортированы по левой границе |
| `testStrToTsmDate` | `'2024-01-01'` → 28401120 (UTC-минуты от epoch) |
| `testStrToTsmDateTime` | `'2024-01-01 10:30'` → 28401750 |
| `testStrToTsmHandlesNullAndEmpty` | null/пустая строка → null |
| `testCompileEmptyScheduleProducesBaseStructure` | минимальный JSON содержит main+overrides |
| `testCompileWithWeekdaysAndDefault` | weekdays и default попадают в JSON |
| `testCompileWithSpecificDate` | конкретная дата в `dates` с ключом `date_tsm` |
| `testCompileWithPeriods` | periods собираются в массив с `start_tsm`/`end_tsm`/`is_work` |
| `testCompileWithOverrides` | overrides собираются с собственной структурой |
| `testCompileInheritsDefaultFromParent` | parent.def наследуется в child |
| `testCompileChildWeekdayOverridesParent` | child.weekday[1] перекрывает parent |
| `testCompileInheritsWeekdaysWhenChildHasNone` | parent.weekdays наследуются полностью |
| `testOverrideDoesNotInheritFromParent` | override не наследует от своего parent |
| `testCompiledJsonIsSerializable` | результат корректно сериализуется в JSON |

### `tests/unit/modules/schedules/CompiledScheduleHelperTest.php` — серверный рантайм

Покрывает [`CompiledScheduleHelper`](../compile/CompiledScheduleHelper.php) — публичный API + утилиты.

| Тест | Что проверяет |
| ---- | ------------- |
| `testStrToTsm`, `testTsmToStr`, `testTsmToDateTsm`, `testDayOfWeek` | конверсия дат-времени |
| `testInBounds` | проверка границ `[start, end)`: левая включена, правая исключена |
| `testIntervalsContains` | поиск интервала, содержащего точку (с учётом minutesFromDay) |
| `testIntervalsSubtractAndAdd` | вычитание и добавление интервалов с no-merge семантикой |
| `testIsWorkDay` | рабочий день / выходной / дата-исключение |
| `testIsWorkTime` | попадание в рабочий интервал, левая граница включена |
| `testGetMeta` | возврат meta активного интервала |
| `testFindOverride` | в override-периоде возвращается override, иначе main |
| `testNextOverride` | первый override с `start_tsm >= tsm` |
| `testFindPeriod` | поиск period, перекрывающего tsm, с фильтром `is_work` |
| `testNextPeriodStrictEnd` | строгая граница `end_tsm > tsm` (закончившийся период не возвращается) |
| `testNextWorkingDateTimeCurrentTime` | время уже рабочее → возвращается оно же |
| `testNextWorkingDateTimeAcrossWeekend` | пятница 20:00 → понедельник 08:00 |
| `testNextWorkingDateTimeBeforeStart` | вход до начала расписания |
| `testGetDatePeriodsBoundary` | период, закончившийся ровно в начале дня, не пересекает день |
| `testConstructorAcceptsJsonString` | конструктор принимает JSON-строку |
| `testNextWorkingMeta` | meta ближайшего рабочего времени |

### `tests/unit/modules/schedules/SchedulesEntriesValidationTest.php` — валидация записей

| Тест | Что проверяет |
| ---- | ------------- |
| `testWeekdayUniquenessRejectsDuplicate` | две записи на один weekday запрещены |
| `testDefaultWeekdayUniquenessRejectsDuplicate` | две записи на `def` запрещены |
| `testDifferentWeekdaysAreAllowed` | разные weekday — ok |
| `testDateUniquenessRejectsDuplicate` | две записи на одну дату запрещены |
| `testPeriodIntersectionRejected` | пересекающиеся периоды запрещены |
| `testNonOverlappingPeriodsAllowed` | несоприкасающиеся периоды — ok |

### `tests/unit/modules/schedules/SchedulesOverrideValidationTest.php` — валидация overrides

| Тест | Что проверяет |
| ---- | ------------- |
| `testNonOverlappingOverridesAllowed` | overrides на непересекающиеся периоды — ok |
| `testOverlappingOverridesRejected` | пересекающиеся overrides на одного и того же родителя запрещены |
| `testNestedOverrideRejected` | override не может быть override-родителем (`override` поверх `override`) |
| `testUnboundedOverrideBlocksSubsequent` | open-ended override блокирует все последующие |

### `tests/unit/modules/schedules/SchedulesLifecycleTest.php` — lifecycle и каскад

| Тест | Что проверяет |
| ---- | ------------- |
| `testSaveSchedulePopulatesCompiledJson` | сохранение Schedules заполняет `compiled_json` через `afterSave` |
| `testSchedulesEntrySaveTriggersRecompile` | `SchedulesEntries::afterSave` перекомпилирует родителя |
| `testSchedulesEntryDeleteTriggersRecompile` | `SchedulesEntries::afterDelete` перекомпилирует родителя |
| `testOverrideSaveRecompilesParent` | сохранение override-расписания триггерит компиляцию родителя |
| `testCascadeRecompilesChildrenNonOverrides` | каскад по `parent_id` обновляет потомков |

### `modules/schedules/tests/unit/SchedulesTest.php` — каркасные unit-тесты модели

37 тестов на:
- статические значения (`tableName`, `$titles`, `$allDaysTitle`, scenario-константы);
- `Schedules::generatePeriodDescription()` (4 кейса: оба/только-start/только-end/полный диапазон);
- словарь (`Schedules::dictionary`) для разных `providingMode`;
- `SchedulesEntries::validateTime`, `validateSchedule`, `validateSchedules`;
- `SchedulesEntries::strTimestampToMinutes`, `intMinutesToStrTimestamp`;
- `scheduleToMinuteInterval`, `scheduleToMinuteIntervals`, `scheduleWithoutMetadata`;
- `periodMetadata`, `minuteIntervalToSchedule`;
- `scheduleMinuteIntervalFitDay`, `scheduleMinuteIntervalOverheadDay` (overnight);
- `scheduleExToMinuteInterval` (с meta / без / некорректный);
- `daysArray`, `isWorkComment`.

### `modules/schedules/tests/unit/TimeIntervalsHelperTest.php` — математика интервалов

48 тестов на [`TimeIntervalsHelper`](../helpers/TimeIntervalsHelper.php):
- `dayMinutesOverheadFix` / `Humanize` (включая `All`-варианты);
- `intervalCut` (внутри / left-overflow / right-overflow / null-границы);
- `intervalCheck` (внутри / границы);
- `intervalIntersect` (touching, без касания);
- `intervalsCompare`, `intervalsSort`;
- `intervalSubtraction`, `intervalsSubtraction`;
- `intervalMerge` (с `touch` и без);
- `intervalTile`.

### `modules/schedules/tests/unit/SchedulesModelCalcFieldsTraitTest.php` — трейт Schedules

25 тестов:
- `getIsOverride`, `getBaseId`;
- `getStartUnixTime`, `getEndUnixTime` (+ кэш);
- `endsBeforeDate`, `startsAfterDate`, `matchDate`;
- `getParentsChain`.

### `modules/schedules/tests/unit/ScheduleEntriesModelCalcFieldsTraitTest.php` — трейт SchedulesEntries

19 тестов:
- `getDay`, `getDayFor` (weekday / def / дата);
- `getSchedulePeriods` (пустой `-`, single, multiple);
- `getIsAcl` (без master, master-не-ACL, master-ACL, кэш);
- `getPreviousWeekDay` (для `def`, без master);
- `getPeriodSchedule` (один день, multi-day, null start/end).

---

## Реализовано (JS)

### `modules/schedules/compile/lib/js/demo.test.js` — JS-рантайм

94 Jest-теста на `ScheduleRuntime` и утилиты.

Тематические блоки:

| Блок | Состав |
| ---- | ------ |
| `strToTsm` | парсинг даты, даты-времени, null/некорректная строка |
| `tsmToStr` | форматирование, null |
| `tsmToDateTsm` | начало дня |
| `dayOfWeek` | пн (1) и вс (7) |
| `inBounds` | внутри / границы / null start/end / null tsm/bounds |
| `intervalsContains` | внутри / вне / границы / пустой массив / null |
| `intervalsSubtract` | середина / полное перекрытие / слева / пустые входы |
| `intervalsAdd` | 11 кейсов NO-MERGE (happy/edge/integration), включая null-override и закрытие разрыва |
| `getDatePeriods` | период охватывает день / начало дня / отсутствие периодов |
| `getDatePeriodsIntervals` | период работы внутри дня |
| `getDateIntervals` | weekday / dates с графиком / dates с `-` / вне границ |
| `isWorkDay` | пн (рабочий) / сб (выходной) / дата-исключение |
| `isWorkTime` | рабочее / нерабочее (до/после) / границы (start вкл, end искл) |
| `getMeta` | meta найдена / вне рабочего |
| `findOverride` | override / fallback на main |
| `findPeriod` | work / non-work |
| `nextOverride` | следующий override |
| `applyPeriodsToDay` | только positive / только negative |
| `filterBefore` | возвращает клон, не мутирует оригинал; запись не на текущий день |
| `findOverride — граничные` | (расширенные кейсы) |
| `Дополнительные edge cases` | расписание без periods / без default |

---

## Что ещё нужно (TODO по тестам)

### PHP

| Приоритет | Что | Где |
| --------- | --- | --- |
| высокий | **Приоритет над override**: дата-исключение `main` внутри окна override перебивает недельный график override; рабочий/нерабочий период `main` накладывается поверх графика override (`getDateIntervals`, `nextWorkingDateTime`) | `CompiledScheduleHelperTest.php` |
| высокий | **Паритет legacy↔compiled**: `Schedules::getDateSchedule()` и `CompiledScheduleHelper::getDateIntervals()` дают одинаковый график на сценариях «override + дата-исключение» и «override + период» | новый `ScheduleLegacyCompiledParityTest.php` |
| высокий | **Компиляция**: перекрытия (`override`) в `compiled_json` не содержат `dates`/`periods`; дата-запись, ошибочно привязанная к override-расписанию, не попадает в его `dates` | `SchedulesCompilerTest.php` |
| высокий | `SchedulesCompiler::compile()` с **глубокой иерархией** (3+ уровня parent_id) и проверкой защиты от циклов на 100 уровнях | `tests/unit/modules/schedules/SchedulesCompilerTest.php` |
| высокий | `CompiledScheduleHelper`: расхождение TZ — `tz_shift_tsm != 0`, проверка что серверный/клиентский результат совпадают на одних и тех же входах | новый `CompiledScheduleTimezoneTest.php` |
| высокий | `getStatus()`/`isWorkTime()` для **колонок grid'а** — интеграционный тест на `views/schedules/columns.php`: серверный и клиентский расчёт должны давать одинаковый ответ для одного и того же `compiled_json` и текущего времени | новый `ScheduleColumnsIntegrationTest.php` |
| средний | `nextWorkingDateTime` через несколько overrides подряд (последовательный пропуск) | `CompiledScheduleHelperTest.php` |
| средний | `nextWorkingDateTime` с пересечением period(non-work) и weekday — длинный нерабочий период «съедает» рабочие дни | `CompiledScheduleHelperTest.php` |
| средний | `getDateIntervals` с `intervals` overnight (22:00-06:00) — корректность переноса на следующий день | `CompiledScheduleHelperTest.php` |
| средний | Acceptance-тест колонки «Активно (live)»: `PageAccessCest::testIndex()` для `SchedulesController` с проверкой наличия классов `.schedule-runtime-status` и data-target в HTML | `tests/acceptance/PageAccessCest.php` |
| низкий | Производительность: компиляция расписания с 365 дат-исключениями + 50 overrides ≤ N мс | новый `SchedulesCompilerPerfTest.php` |
| низкий | `recompileCascade` стабилен при циклах `parent_id` (искусственно повреждённая БД) | `SchedulesLifecycleTest.php` |

### JS

| Приоритет | Что |
| --------- | --- |
| высокий | **Приоритет над override**: дата-исключение/период `main` перебивают/накладываются поверх недельного графика override (`getDateIntervals`, `nextWorkingDateTime`); проверить, что результат совпадает с PHP-рантаймом |
| высокий | Заполнить **все** Test Cases из таблиц `compile.md` — сейчас покрыто только happy-path. Особо: Edge/Error/Integration строки для `getDatePeriods`, `getDatePeriodsIntervals`, `getDateIntervals`, `isWorkDay`, `isWorkTime`, `getMeta`, `nextWorkingDateTime`, `nextWorkingMeta`, `findOverride`, `nextOverride`, `findPeriod`, `nextPeriod`, `nextWorkDateEntry`, `nextWeekDayEntry`, `nextRecord` |
| высокий | Тест на синхронизацию JS- и PHP-рантайма: общий набор фикстур, оба должны давать одинаковый ответ. Сейчас фикстуры дублируются, что чревато расхождением |
| средний | Тест на `tz_shift_tsm`: вход «локальное время как UTC», результат совпадает с серверным `CompiledScheduleHelper` |
| средний | DST (если когда-нибудь поддержим динамический TZ): расписание через дату перевода стрелок |
| низкий | Браузерный smoke-тест `schedule-runtime-status.js`: рендерит `●`/`○`, перерисовывает по таймеру |

### Acceptance

| Приоритет | Что |
| --------- | --- |
| высокий | Парные `testXxx()` для **всех** action в `SchedulesController`, `SchedulesEntriesController`, `ScheduledAccessController` (см. правила в [`tests/acceptance.md`](../../../tests/acceptance.md)). Проверить через `tests/acceptance-todo.md` — какие сценарии ещё в долге |
| средний | Сценарий на правку `SchedulesEntries` через Kartik Editable (наследуемый `ArmsBaseController::testEditable()`) |

---

## Согласование PHP/JS-рантайма

Сейчас `CompiledScheduleHelperTest.php` (PHP) и `demo.test.js` (JS) имеют **дублирующиеся** наборы фикстур и кейсов. Это работает, но любая правка в одной стороне рискует разъехаться с другой.

**Рекомендация (вынесена в TODO):** общий JSON-файл фикстур (`tests/_data/schedule_runtime_cases.json`) с массивом `{compiled_json, input, expected}`, который читают и PHP-, и JS-тесты. Тогда:

- Любой новый кейс добавляется в одном файле;
- Расхождение реализаций ловится автоматически на CI;
- В readme достаточно описать формат и принцип, а не дублировать сами кейсы.

---

## Итоговые цифры

| Слой | Файл | Тестов |
| ---- | ---- | ------ |
| PHP, модуль | `modules/schedules/tests/unit/SchedulesTest.php` | 37 |
| PHP, модуль | `modules/schedules/tests/unit/TimeIntervalsHelperTest.php` | 48 |
| PHP, модуль | `modules/schedules/tests/unit/SchedulesModelCalcFieldsTraitTest.php` | 25 |
| PHP, модуль | `modules/schedules/tests/unit/ScheduleEntriesModelCalcFieldsTraitTest.php` | 19 |
| PHP, проект | `tests/unit/modules/schedules/SchedulesCompilerTest.php` | 19 |
| PHP, проект | `tests/unit/modules/schedules/CompiledScheduleHelperTest.php` | 20 |
| PHP, проект | `tests/unit/modules/schedules/SchedulesEntriesValidationTest.php` | 6 |
| PHP, проект | `tests/unit/modules/schedules/SchedulesOverrideValidationTest.php` | 4 |
| PHP, проект | `tests/unit/modules/schedules/SchedulesLifecycleTest.php` | 5 |
| **PHP итого** |  | **183** |
| JS | `modules/schedules/compile/lib/js/demo.test.js` | 94 |
