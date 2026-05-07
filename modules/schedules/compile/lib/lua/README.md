# ScheduleRuntime — Lua 5.2 порт

Порт JS-библиотеки [`lib/js/demo.js`](../js/demo.js) для работы со
скомпилированными расписаниями (`schedules.compiled_json`) на Lua 5.2.
Предназначен прежде всего для встраивания в Asterisk (см. [TODO.md](../../TODO.md), Этап 6).

## Состав

- `schedule_runtime.lua` — модуль с классом `ScheduleRuntime` и хелперами
  (`strToTsm`, `tsmToStr`, `tsmToDateTsm`, `dayOfWeek`, `inBounds`,
  `intervalsContains`, `intervalsSubtract`, `intervalsAdd`).
- `schedule_runtime_test.lua` — 105 тестов на минимальном самописном
  test-runner'е (без внешних зависимостей).

## Запуск тестов

Lua 5.2 установлен в `C:\Programs\lua52\`:

```cmd
"C:\Programs\lua52\lua52.exe" schedule_runtime_test.lua
```

Скрипт сам добавляет свою директорию в `package.path`, поэтому запускать
можно из любого места. Код выхода `0` — все тесты прошли, `1` — падение.

## Использование

```lua
local rt = require('schedule_runtime')

-- compiled — обычная Lua-таблица той же структуры, что и compiled_json
-- (см. шапку schedule_runtime.lua и compile/compile.md).
-- Если у вас на руках JSON-строка, распарсите её любым удобным парсером
-- (cjson / dkjson / lua-rapidjson — на ваш выбор) и передайте таблицу.
local s = rt.ScheduleRuntime.new(compiled)

s:isWorkDay('2024-01-08')             -- true/false
s:isWorkTime('2024-01-08 10:00')      -- true/false
s:getMeta('2024-01-05 10:00')         -- таблица meta или nil
s:nextWorkingDateTime('2024-01-05 20:00') -- 'YYYY-MM-DD HH:MM' или nil
s:nextWorkingMeta('2024-01-05 03:00')     -- meta ближайшего рабочего интервала
```

Все *_tsm — это **UTC-минуты от Unix-эпохи**. Парсинг строк выполняется
вручную (без `os.time`/локали), поэтому результаты не зависят от системной TZ.

Парные функции:

```lua
rt.strToTsm('2024-01-01')          -- 28401120
rt.tsmToStr(28401120)              -- '2024-01-01 00:00'
rt.tsmToDateTsm(28401750)          -- 28401120
rt.dayOfWeek(28401120)             -- 1 (понедельник)
rt.inBounds(tsm, { start_tsm, end_tsm })
rt.intervalsContains(intervals, tsm)
rt.intervalsSubtract(intervals, subtract)
rt.intervalsAdd(intervals, override)
```

## Отличия от JS-версии

- `getDateIntervals(date)` принимает либо число `tsm`, либо строку
  `'YYYY-MM-DD'`/`'YYYY-MM-DD HH:MM'`, и при строке корректно её парсит.
  В JS-версии (`demo.js`) тот же метод документирует параметр как `string`,
  но фактически рассчитан на `tsm`-число; передача строки в JS-тестах
  «случайно» работает через NaN-арифметику и попадает в default override.
- Lua-версия не зависит от `os.time` / системной TZ. Календарь реализован
  по алгоритму Howard Hinnant (`days_from_civil` / `civil_from_days`).

В остальном поведение совпадает 1:1 с JS-портом, включая:
- строгую семантику `nextPeriod` (`end_tsm > tsm`),
- неизменяемость записи в `filterBefore` (возвращается клон),
- порядок `dates` по числовому значению ключа,
- отсутствие склейки соседних интервалов в `intervalsAdd`.
