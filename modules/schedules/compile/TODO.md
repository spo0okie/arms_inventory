# TODO по реализации компиляции расписаний

Трекер задач для плана [compile.md](compile.md). Статусы помечаются галочками:
`[ ]` — pending, `[~]` — in progress, `[x]` — done.

---

## Этап 0. Причёсывание плана и фиксация открытых вопросов

- [x] Удалить дубликат секции `intervalsAdd` (compile.md:490-551 — повторяет 253-314).
- [x] Починить внутренние ссылки `modules/schedules/plans/compile.md` → `modules/schedules/compile/compile.md` (строки 260, 448-449, 497-498).
- [x] Заменить `_findWeekdayEntry` на `nextWeekDayEntry` в тексте плана (строка 922).
- [x] Уточнить в плане семантику `nextPeriod`: `end_tsm > tsm` (строго, т.к. период `[start, end)` с `end == tsm` уже закончился).
- [x] Зафиксировать тип колонки: `schedules.compiled_json TEXT` (размер по умолчанию).
- [x] Зафиксировать TZ-контракт: все `_tsm` хранятся в UTC-минутах; поле `tz` — только для парсинга входных строк и отображения.
- [x] Зафиксировать горизонт компиляции: компилируем все данные без ограничения (решение по умолчанию из плана).
- [x] Вынести валидации консистентности (непересечение periods/overrides, уникальность weekdays/dates) из раздела «Ограничения» в конкретные требования к `rules()`/`beforeValidate()`.
- [x] Применить правку `nextPeriod` (`end_tsm >= tsm` → `end_tsm > tsm`) в [demo.js](lib/js/demo.js).

## Этап 1. БД и валидация

- [x] Миграция `m260420_033806_add_compiled_json_to_schedules`: добавить колонку `schedules.compiled_json TEXT NULL`.
- [x] Правило валидации: periods одного расписания не могут пересекаться. _(уже было: `SchedulesEntries::rules()` + `periodsIntersect`)_
- [x] Правило валидации: overrides (schedules с `override_id = parent`) не могут пересекаться по `[start_date, end_date)`. _(уже было: `Schedules::rules()` + `matchDate`)_
- [x] Правило валидации: в одном расписании — уникальность weekday-entries (1..7, def). _(исправлено: ранее ранний return пропускал проверку для weekday-ключей)_
- [x] Правило валидации: в одном расписании — уникальность date-entries по дате. _(уже было)_
- [x] Unit-тесты валидации в `tests/unit/modules/schedules/`: 6 тестов в `SchedulesEntriesValidationTest`, 4 теста в `SchedulesOverrideValidationTest`.

## Этап 2. PHP-компиляция (`SchedulesCompiler::compile`)

- [x] Хелпер [`SchedulesCompiler`](SchedulesCompiler.php) (namespace `app\modules\schedules\compile`):
  - `compile(Schedules $schedule): array` — плоский массив, готовый к `json_encode`.
  - Парсинг графиков в `intervals` + извлечение `meta` из `{...}`.
  - Расчёт `_tsm` для start/end/date/period (UTC-минуты).
  - Сортировки: `periods`/`overrides` по `start_tsm`, `dates` и `weekdays` по ключу.
  - Унификация структуры main и override (periods только в main).
- [x] Unit-тесты в `tests/unit/modules/schedules/SchedulesCompilerTest.php` (15 тестов).
- [x] Сборка цепочки предков `parent_id` до корня в плоский main: запись ребёнка перекрывает запись предка по тому же ключу (weekday/date/def). Override-расписания не наследуют. Защита от циклов `parent_id` на 100 уровней. Тесты: 4 новых.

## Этап 3. Жизненный цикл компиляции

- [x] `Schedules::afterSave()` — вызов `recompileCascade()` на самой записи (или на родителе, если это override).
- [x] Каскад: перекомпиляция overrides по `override_id` и потомков по `parent_id`; защита от рекурсии через `visited + $compiling` + `findOne()` свежего экземпляра.
- [x] `SchedulesEntries::afterSave()` / `afterDelete()` — триггер перекомпиляции родительского `Schedules`.
- [x] Unit-тесты лайфцикла: `tests/unit/modules/schedules/SchedulesLifecycleTest.php` (5 тестов, включая каскад).
- [x] SQL-дамп `tests/_data/arms_demo.sql` обновлён: добавлена колонка `compiled_json` в `schedules`.

## Этап 4. Достройка JS-рантайма

- [x] Дополнен `demo.test.js` расширенными кейсами для `nextOverride`, `nextPeriod`, `getDatePeriods`, `nextWorkingDateTime`, `findOverride` — 92 теста (было 73).
- [x] Правка `nextPeriod` применена (на Этапе 0).
- [x] В `nextWorkingDateTime` исправлено использование `dayStart` — теперь берётся `entry.date_tsm`, а не `tsmToDateTsm(pos)` (иначе для пятницы после работы возвращалось сегодняшнее утро вместо понедельника).
- [x] `nextWorkingDateTime` теперь корректно возвращает `pos` при попадании в середину рабочего интервала (было: возвращал начало интервала, даже если оно раньше pos).
- [x] Ревизия `filterBefore` на предмет мутаций: тест `filterBefore — не мутирует оригинал` в `demo.test.js`.
- [x] AssetBundle: `modules/schedules/assets/ScheduleRuntimeAsset.php` с sourcePath на `compile/lib/js/demo.js` — подключается через `ScheduleRuntimeAsset::register($this)`.
- [ ] Подключить AssetBundle к view (календари/графики) — будет сделано вместе с использованием.

## Этап 5. PHP `CompiledScheduleHelper` (серверный рантайм)

- [x] Порт публичного API + всех внутренних/утилитных функций из `ScheduleRuntime` в `modules/schedules/compile/CompiledScheduleHelper.php`. Принимает либо массив, либо JSON-строку.
- [x] Общая таблица test cases в PHPUnit: `tests/unit/modules/schedules/CompiledScheduleHelperTest.php` — 20 тестов, включая: isWorkDay/isWorkTime/getMeta/nextWorkingDateTime/nextWorkingMeta + findOverride/nextOverride/findPeriod/nextPeriod (строгая семантика `end > tsm`) + intervalsSubtract/intervalsAdd + getDatePeriods граничные случаи.
- [ ] Интеграция в контроллеры/helper'ы вместо прямой работы с `SchedulesEntries` — отдельная задача, требует точечного рефакторинга и будет выполнена по мере необходимости.

## Этап 6. Lua-рантайм для Asterisk

- [ ] Реализация минимального набора: `isWorkDay`, `isWorkTime`, `getMeta`, `nextWorkingDateTime`.
- [ ] Чтение `compiled_json` из БД/файла.
- [ ] Инструкция по интеграции (`modules/schedules/docs/asterisk.md` или аналог).
