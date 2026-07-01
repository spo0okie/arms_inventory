--[[
=============================================================================
Тесты ScheduleRuntime (Lua 5.2)
=============================================================================
Запуск: lua52 schedule_runtime_test.lua
Покрывает то же поведение, что и lib/js/demo.test.js, но с корректной
семантикой строкового аргумента в getDateIntervals (см. порт в demo.js
полагается на NaN-арифметику; здесь строки парсятся явно).
=============================================================================
]]

-- Делаем модуль доступным независимо от cwd
local script_path = (debug.getinfo(1).source:match('@?(.*[/\\])')) or './'
package.path      = script_path .. '?.lua;' .. package.path

local rt = require('schedule_runtime')

local ScheduleRuntime    = rt.ScheduleRuntime
local strToTsm           = rt.strToTsm
local tsmToStr           = rt.tsmToStr
local tsmToDateTsm       = rt.tsmToDateTsm
local dayOfWeek          = rt.dayOfWeek
local inBounds           = rt.inBounds
local intervalsContains  = rt.intervalsContains
local intervalsSubtract  = rt.intervalsSubtract
local intervalsAdd       = rt.intervalsAdd

-- =============================================================================
-- Минимальный test runner
-- =============================================================================

local total, passed, failed = 0, 0, 0
local groupStack            = {}
local failures              = {}

local function describe(name, fn)
    groupStack[#groupStack + 1] = name
    fn()
    groupStack[#groupStack]     = nil
end

local function fullName(testName)
    if #groupStack == 0 then return testName end
    return table.concat(groupStack, ' › ') .. ' › ' .. testName
end

local function dump(v, seen)
    seen = seen or {}
    local t = type(v)
    if t == 'string'  then return string.format('%q', v) end
    if t ~= 'table'   then return tostring(v) end
    if seen[v]        then return '<cycle>' end
    seen[v] = true

    local isArray, n, maxIdx = true, 0, 0
    for k, _ in pairs(v) do
        n = n + 1
        if type(k) == 'number' and k >= 1 and k == math.floor(k) then
            if k > maxIdx then maxIdx = k end
        else
            isArray = false
        end
    end
    if n == 0 then return '{}' end
    if isArray and n == maxIdx then
        local parts = {}
        for i = 1, n do parts[i] = dump(v[i], seen) end
        return '[' .. table.concat(parts, ', ') .. ']'
    end
    local parts, keys = {}, {}
    for k, _ in pairs(v) do keys[#keys + 1] = k end
    table.sort(keys, function(a, b) return tostring(a) < tostring(b) end)
    for _, k in ipairs(keys) do
        parts[#parts + 1] = tostring(k) .. '=' .. dump(v[k], seen)
    end
    return '{' .. table.concat(parts, ', ') .. '}'
end

local function deepEqual(a, b)
    if a == b then return true end
    if type(a) ~= 'table' or type(b) ~= 'table' then return false end
    for k, v in pairs(a) do
        if not deepEqual(v, b[k]) then return false end
    end
    for k, _ in pairs(b) do
        if a[k] == nil then return false end
    end
    return true
end

local function fail(msg)
    error(msg, 3)
end

local function assertEq(actual, expected, label)
    if actual ~= expected then
        fail((label and (label .. ': ') or '') ..
            'expected ' .. dump(expected) .. ', got ' .. dump(actual))
    end
end

local function assertDeepEq(actual, expected, label)
    if not deepEqual(actual, expected) then
        fail((label and (label .. ': ') or '') ..
            'expected ' .. dump(expected) .. ', got ' .. dump(actual))
    end
end

local function assertNil(actual, label)
    if actual ~= nil then
        fail((label and (label .. ': ') or '') .. 'expected nil, got ' .. dump(actual))
    end
end

local function assertTrue(actual, label)
    if actual ~= true then
        fail((label and (label .. ': ') or '') .. 'expected true, got ' .. dump(actual))
    end
end

local function assertFalse(actual, label)
    if actual ~= false then
        fail((label and (label .. ': ') or '') .. 'expected false, got ' .. dump(actual))
    end
end

local function assertNotNil(actual, label)
    if actual == nil then
        fail((label and (label .. ': ') or '') .. 'expected non-nil')
    end
end

local function assertGreater(actual, threshold, label)
    if not (type(actual) == 'number' and actual > threshold) then
        fail((label and (label .. ': ') or '') ..
            'expected > ' .. tostring(threshold) .. ', got ' .. dump(actual))
    end
end

local function test(name, fn)
    total = total + 1
    local ok, err = pcall(fn)
    if ok then
        passed = passed + 1
    else
        failed = failed + 1
        local fn_name = fullName(name)
        failures[#failures + 1] = fn_name .. '\n    ' .. tostring(err)
    end
end

-- =============================================================================
-- Тестовые данные
-- =============================================================================

local function buildTestSchedule()
    return {
        tz           = 'Asia/Yekaterinburg',
        tz_shift_tsm = 300,
        compiled     = '2024-01-15T10:30:00Z',
        main = {
            name      = 'График работы офиса',
            start     = '2024-01-01',
            start_tsm = 28401120,
            ['end']   = nil,
            end_tsm   = nil,
            default   = { schedule = '08:00-17:00', intervals = { { 480, 1020, {} } } },
            weekdays  = {
                ['1'] = { schedule = '08:00-17:30', intervals = { { 480, 1050, {} } },                      comment = 'Понедельник' },
                ['5'] = { schedule = '08:00-16:00', intervals = { { 480, 960, { user = 'pupkin' } } },      comment = 'Пятница' },
                ['6'] = { schedule = '-',           intervals = {} },
                ['7'] = { schedule = '-',           intervals = {} },
            },
            dates = {
                ['28401120'] = { date_tsm = 28401120, schedule = '-',           intervals = {},                       comment = 'Новый год' },
                ['28402560'] = { date_tsm = 28402560, schedule = '10:00-15:00', intervals = { { 600, 900, {} } } },
            },
            periods = {
                { start_tsm = 28414680, end_tsm = 28418139, is_work = true,  comment = 'Работали непрерывно' },
                { start_tsm = 28446430, end_tsm = 28448047, is_work = false, comment = 'Аварийное отключение' },
            },
        },
        overrides = {
            {
                name      = 'Лето 2024',
                start_tsm = 28620000,
                end_tsm   = 28752400,
                default   = { schedule = '09:00-18:00', intervals = { { 540, 1080, {} } } },
                weekdays  = {},
                dates     = {},
                periods   = {},
            },
        },
    }
end

-- =============================================================================
-- ТЕСТЫ
-- =============================================================================

describe('strToTsm', function()
    test('должен преобразовать строку даты в tsm', function()
        assertEq(strToTsm('2024-01-01'), 28401120)
    end)
    test('должен преобразовать строку даты-времени в tsm', function()
        assertEq(strToTsm('2024-01-01 10:30'), 28401120 + 10 * 60 + 30)
    end)
    test('должен вернуть nil для пустой строки', function()
        assertNil(strToTsm(''))
    end)
    test('должен вернуть nil для nil', function()
        assertNil(strToTsm(nil))
    end)
    test('должен вернуть nil для некорректной строки', function()
        assertNil(strToTsm('invalid'))
    end)
    test('должен вернуть nil для невалидного месяца', function()
        assertNil(strToTsm('2024-13-01'))
    end)
end)

describe('tsmToStr', function()
    test('должен преобразовать tsm в полную строку', function()
        assertEq(tsmToStr(28401120), '2024-01-01 00:00')
    end)
    test('должен корректно обработать минуты', function()
        assertEq(tsmToStr(28401120 + 10 * 60 + 30), '2024-01-01 10:30')
    end)
    test('должен вернуть nil для nil', function()
        assertNil(tsmToStr(nil))
    end)
    test('round-trip strToTsm -> tsmToStr', function()
        assertEq(tsmToStr(strToTsm('2024-06-15 08:45')), '2024-06-15 08:45')
    end)
end)

describe('tsmToDateTsm', function()
    test('должен преобразовать tsm в начало дня', function()
        assertEq(tsmToDateTsm(28401750), 28401120)
    end)
    test('должен вернуть nil для nil', function()
        assertNil(tsmToDateTsm(nil))
    end)
end)

describe('dayOfWeek', function()
    test('должен вернуть 1 для понедельника (2024-01-01)', function()
        assertEq(dayOfWeek(28401120), 1)
    end)
    test('должен вернуть 7 для воскресенья (2024-01-07)', function()
        assertEq(dayOfWeek(28409760), 7)
    end)
    test('должен вернуть 4 для четверга (epoch 1970-01-01)', function()
        assertEq(dayOfWeek(0), 4)
    end)
    test('должен вернуть nil для nil', function()
        assertNil(dayOfWeek(nil))
    end)
end)

describe('inBounds', function()
    local bounds = { start_tsm = 28401120, end_tsm = 28429200 }
    test('true когда tsm внутри границ', function()
        assertTrue(inBounds(28416600, bounds))
    end)
    test('true когда tsm точно на start', function()
        assertTrue(inBounds(28401120, bounds))
    end)
    test('false когда tsm точно на end', function()
        assertFalse(inBounds(28429200, bounds))
    end)
    test('false когда tsm раньше start', function()
        assertFalse(inBounds(28401119, bounds))
    end)
    test('false когда tsm позже end', function()
        assertFalse(inBounds(28429201, bounds))
    end)
    test('true для nil start', function()
        assertTrue(inBounds(100, { start_tsm = nil, end_tsm = 28429200 }))
    end)
    test('true для nil end', function()
        assertTrue(inBounds(28500000, { start_tsm = 28401120, end_tsm = nil }))
    end)
    test('false для nil tsm', function()
        assertFalse(inBounds(nil, bounds))
    end)
    test('false для nil bounds', function()
        assertFalse(inBounds(28416600, nil))
    end)
end)

describe('intervalsContains', function()
    local intervals = { { 480, 1020, { duty = 'Иванов' } }, { 1200, 1320, {} } }
    test('должен найти интервал содержащий tsm', function()
        local dayStart = tsmToDateTsm(28401120) -- начало дня
        local result   = intervalsContains(intervals, dayStart + 600)
        assertDeepEq(result, { 480, 1020, { duty = 'Иванов' } })
    end)
    test('должен вернуть nil когда tsm вне интервалов', function()
        local dayStart = tsmToDateTsm(28401120)
        assertNil(intervalsContains(intervals, dayStart + 1080))
    end)
    test('левая граница интервала включена', function()
        local dayStart = tsmToDateTsm(28401120)
        assertNotNil(intervalsContains(intervals, dayStart + 480))
    end)
    test('правая граница интервала не включена', function()
        local dayStart = tsmToDateTsm(28401120)
        assertNil(intervalsContains(intervals, dayStart + 1020))
    end)
    test('пустой массив', function()
        assertNil(intervalsContains({}, 600))
    end)
    test('nil массив', function()
        assertNil(intervalsContains(nil, 600))
    end)
end)

describe('intervalsSubtract', function()
    test('вычесть из середины — две части', function()
        local result = intervalsSubtract({ { 480, 1020, {} } }, { 600, 900, {} })
        assertDeepEq(result, { { 480, 600, {} }, { 900, 1020, {} } })
    end)
    test('полное перекрытие — пусто', function()
        assertDeepEq(intervalsSubtract({ { 480, 1020, {} } }, { 400, 1200, {} }), {})
    end)
    test('усечение слева', function()
        assertDeepEq(intervalsSubtract({ { 480, 1020, {} } }, { 300, 500, {} }),
            { { 500, 1020, {} } })
    end)
    test('усечение справа', function()
        assertDeepEq(intervalsSubtract({ { 480, 1020, {} } }, { 1000, 1100, {} }),
            { { 480, 1000, {} } })
    end)
    test('пустой subtract — копия', function()
        assertDeepEq(intervalsSubtract({ { 480, 1020, {} } }, { 600, 600, {} }),
            { { 480, 1020, {} } })
    end)
    test('пустые интервалы', function()
        assertDeepEq(intervalsSubtract({}, { 600, 900, {} }), {})
    end)
    test('нет пересечения — копия', function()
        assertDeepEq(intervalsSubtract({ { 480, 1020, {} } }, { 1100, 1200, {} }),
            { { 480, 1020, {} } })
    end)
end)

describe('intervalsAdd', function()
    test('Happy: вставка БЕЗ склейки даёт три отдельных интервала', function()
        local result = intervalsAdd({ { 480, 1020, {} } }, { 600, 900, {} })
        assertDeepEq(result, {
            { 480, 600, {} },
            { 600, 900, {} },
            { 900, 1020, {} },
        })
    end)
    test('Happy: override не пересекается с базовым', function()
        local result = intervalsAdd({ { 480, 1020, {} } }, { 300, 500, {} })
        assertDeepEq(result, { { 300, 500, {} }, { 500, 1020, {} } })
    end)
    test('Happy: несколько базовых — вставка между', function()
        local result = intervalsAdd({ { 480, 600, {} }, { 700, 1020, {} } }, { 550, 750, {} })
        assertDeepEq(result, {
            { 480, 550, {} },
            { 550, 750, {} },
            { 750, 1020, {} },
        })
    end)
    test('Edge: пустой базовый — override становится единственным', function()
        assertDeepEq(intervalsAdd({}, { 600, 900, {} }), { { 600, 900, {} } })
    end)
    test('Edge: пустой override — базовый не меняется', function()
        assertDeepEq(intervalsAdd({ { 480, 1020, {} } }, {}), { { 480, 1020, {} } })
    end)
    test('Edge: nil override — базовый не меняется', function()
        assertDeepEq(intervalsAdd({ { 480, 1020, {} } }, nil), { { 480, 1020, {} } })
    end)
    test('Edge: override полностью покрывает', function()
        assertDeepEq(intervalsAdd({ { 480, 1020, {} } }, { 400, 1200, {} }),
            { { 400, 1200, {} } })
    end)
    test('Edge: meta override сохраняется', function()
        local result = intervalsAdd({ { 480, 600, {} }, { 700, 1020, {} } },
            { 550, 750, { duty = 'test' } })
        assertDeepEq(result, {
            { 480, 550, {} },
            { 550, 750, { duty = 'test' } },
            { 750, 1020, {} },
        })
    end)
    test('Edge: override закрывает разрыв — без склейки', function()
        local result = intervalsAdd({ { 480, 720, {} }, { 780, 1020, {} } }, { 600, 900, {} })
        assertDeepEq(result, {
            { 480, 600, {} },
            { 600, 900, {} },
            { 900, 1020, {} },
        })
    end)
    test('Empty: оба пусто', function()
        assertDeepEq(intervalsAdd({}, {}), {})
    end)
end)

describe('ScheduleRuntime — getDatePeriods', function()
    test('Happy: период перекрывает день полностью', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- день 2024-01-11 = 28401120 + 10*1440 = 28415520
        local periods = s:getDatePeriods(28415520)
        assertGreater(#periods, 0)
    end)
    test('Empty: расписание без периодов', function()
        local s = ScheduleRuntime.new({
            main = { periods = {}, start_tsm = 0, end_tsm = nil },
            overrides = {},
        })
        assertDeepEq(s:getDatePeriods(28414800), {})
    end)
    test('Edge: period.end_tsm == dayStart НЕ включается', function()
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 0, end_tsm = nil,
                periods = { { start_tsm = 28414080, end_tsm = 28415520, is_work = true } },
            },
            overrides = {},
        })
        assertDeepEq(s:getDatePeriods(28415520), {})
    end)
    test('Edge: минимальное пересечение', function()
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 0, end_tsm = nil,
                periods = { { start_tsm = 28415519, end_tsm = 28415521, is_work = true } },
            },
            overrides = {},
        })
        assertEq(#s:getDatePeriods(28415520), 1)
    end)
    test('Edge: период следующего дня НЕ включается', function()
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 0, end_tsm = nil,
                periods = { { start_tsm = 28416960, end_tsm = 28418400, is_work = true } },
            },
            overrides = {},
        })
        assertDeepEq(s:getDatePeriods(28415520), {})
    end)
end)

describe('ScheduleRuntime — getDateIntervals', function()
    test('Happy: понедельник из weekdays', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-08 — понедельник
        local intervals = s:getDateIntervals('2024-01-08')
        assertDeepEq(intervals, { { 480, 1050, {} } })
    end)
    test('Happy: пятница с meta', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-05 — пятница
        local intervals = s:getDateIntervals('2024-01-05')
        assertDeepEq(intervals, { { 480, 960, { user = 'pupkin' } } })
    end)
    test('Happy: dates имеет приоритет над weekdays', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-02 → tsm 28402560, dates['28402560'] → [[600, 900]]
        local intervals = s:getDateIntervals('2024-01-02')
        assertDeepEq(intervals, { { 600, 900, {} } })
    end)
    test('Happy: dates пустые → выходной', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        local intervals = s:getDateIntervals('2024-01-01')
        assertDeepEq(intervals, {})
    end)
    test('Happy: суббота → выходной (пустой weekdays[6])', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-06 — суббота
        local intervals = s:getDateIntervals('2024-01-06')
        assertDeepEq(intervals, {})
    end)
    test('Happy: дата в override Лето 2024', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-07-01 (понедельник летом)
        local intervals = s:getDateIntervals('2024-07-01')
        assertDeepEq(intervals, { { 540, 1080, {} } })
    end)
    test('Edge: дата раньше start_tsm', function()
        local s         = ScheduleRuntime.new(buildTestSchedule())
        local intervals = s:getDateIntervals('2023-01-01')
        assertDeepEq(intervals, {})
    end)
    test('Edge: расписание без default → пусто', function()
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 28401120, end_tsm = nil,
                default = nil, weekdays = {}, dates = {}, periods = {},
            },
            overrides = {},
        })
        assertDeepEq(s:getDateIntervals('2024-01-15'), {})
    end)
end)

describe('ScheduleRuntime — isWorkDay', function()
    test('Happy: рабочий день (понедельник)', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertTrue(s:isWorkDay('2024-01-08'))
    end)
    test('Happy: выходной (суббота)', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertFalse(s:isWorkDay('2024-01-06'))
    end)
    test('Happy: дата-исключение с рабочим графиком', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertTrue(s:isWorkDay('2024-01-02'))
    end)
    test('Happy: дата-исключение выходной', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertFalse(s:isWorkDay('2024-01-01'))
    end)
    test('Edge: nil', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertFalse(s:isWorkDay(nil))
    end)
end)

describe('ScheduleRuntime — isWorkTime', function()
    test('Happy: рабочее время', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertTrue(s:isWorkTime('2024-01-08 10:00'))
    end)
    test('Happy: после графика', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertFalse(s:isWorkTime('2024-01-08 18:00'))
    end)
    test('Happy: до графика', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertFalse(s:isWorkTime('2024-01-08 07:00'))
    end)
    test('Edge: точно start (включён)', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertTrue(s:isWorkTime('2024-01-08 08:00'))
    end)
    test('Edge: точно end (НЕ включён)', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- среда — default 08:00-17:00
        assertFalse(s:isWorkTime('2024-01-03 17:00'))
    end)
    test('Edge: положительный период расширяет рабочее время', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-11 02:00 — внутри positive периода 2024-01-10 10:00 .. 2024-01-12 22:59
        assertTrue(s:isWorkTime('2024-01-11 02:00'))
    end)
    test('Edge: отрицательный период убирает рабочее время', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-02-01 16:00 — в neg периоде 2024-02-01 15:10 .. 2024-02-02 18:17
        assertFalse(s:isWorkTime('2024-02-01 16:00'))
    end)
end)

describe('ScheduleRuntime — getMeta', function()
    test('Happy: meta на пятницу', function()
        local s    = ScheduleRuntime.new(buildTestSchedule())
        local meta = s:getMeta('2024-01-05 10:00')
        assertDeepEq(meta, { user = 'pupkin' })
    end)
    test('Happy: вне рабочего времени', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertNil(s:getMeta('2024-01-08 18:00'))
    end)
end)

describe('ScheduleRuntime — findOverride', function()
    test('Happy: попадание в override', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local override = s:findOverride(strToTsm('2024-07-01 10:00'))
        assertEq(override.name, 'Лето 2024')
    end)
    test('Happy: fallback на main', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local target = s:findOverride(strToTsm('2024-01-15 10:00'))
        assertEq(target.name, 'График работы офиса')
    end)
    test('Edge: точно на start override — включено', function()
        local s = ScheduleRuntime.new({
            main = { name = 'main', start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = { { name = 'O', start_tsm = 28416000, end_tsm = 28427000 } },
        })
        assertEq(s:findOverride(28416000).name, 'O')
    end)
    test('Edge: точно на end override — НЕ включено, fallback main', function()
        local s = ScheduleRuntime.new({
            main = { name = 'main', start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = { { name = 'O', start_tsm = 28416000, end_tsm = 28427000 } },
        })
        assertEq(s:findOverride(28427000).name, 'main')
    end)
    test('Empty: пустой overrides → main', function()
        local s = ScheduleRuntime.new({
            main = { name = 'main', start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {},
        })
        assertEq(s:findOverride(28416600).name, 'main')
    end)
end)

describe('ScheduleRuntime — findPeriod', function()
    test('Happy: work период', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local p = s:findPeriod(strToTsm('2024-01-11 12:00'), true)
        assertNotNil(p)
        assertEq(p.is_work, true)
    end)
    test('Happy: non-work период', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local p = s:findPeriod(strToTsm('2024-02-01 16:00'), false)
        assertNotNil(p)
        assertEq(p.is_work, false)
    end)
    test('Edge: вне всех периодов', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        assertNil(s:findPeriod(strToTsm('2024-03-01 10:00'), nil))
    end)
end)

describe('ScheduleRuntime — nextOverride', function()
    test('Happy: следующий override', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = { { name = 'A', start_tsm = 28419600, end_tsm = 28427000 } },
        })
        assertEq(s:nextOverride(28416600).name, 'A')
    end)
    test('Happy: точное совпадение start_tsm', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = { { name = 'A', start_tsm = 28419600, end_tsm = 28427000 } },
        })
        assertEq(s:nextOverride(28419600).name, 'A')
    end)
    test('Happy: первый с start_tsm >= tsm', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {
                { name = 'A', start_tsm = 28401120, end_tsm = 28414800 },
                { name = 'B', start_tsm = 28416600, end_tsm = 28427000 },
                { name = 'C', start_tsm = 28429200, end_tsm = 28440000 },
            },
        })
        assertEq(s:nextOverride(28414800).name, 'B')
    end)
    test('Edge: все overrides раньше tsm → nil', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {
                { name = 'A', start_tsm = 28401120, end_tsm = 28414800 },
                { name = 'B', start_tsm = 28416600, end_tsm = 28427000 },
            },
        })
        assertNil(s:nextOverride(28500000))
    end)
    test('Empty: пустой overrides → nil', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {},
        })
        assertNil(s:nextOverride(28416600))
    end)
end)

describe('ScheduleRuntime — nextPeriod (строгая семантика end > tsm)', function()
    test('Happy: период впереди возвращается', function()
        local s = ScheduleRuntime.new({
            main      = {
                start_tsm = 0, end_tsm = nil,
                periods   = { { start_tsm = 28414680, end_tsm = 28418139, is_work = true } },
            },
            overrides = {},
        })
        assertNotNil(s:nextPeriod(28415000, true))
    end)
    test('Edge: end_tsm == tsm — пропускается', function()
        local s = ScheduleRuntime.new({
            main      = {
                start_tsm = 0, end_tsm = nil,
                periods   = { { start_tsm = 28414680, end_tsm = 28418139, is_work = true } },
            },
            overrides = {},
        })
        assertNil(s:nextPeriod(28418139, true))
    end)
    test('Empty: нет периодов', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {},
        })
        assertNil(s:nextPeriod(28416600, true))
    end)
end)

describe('ScheduleRuntime — applyPeriodsToDay', function()
    test('Happy: только positive период (без склейки)', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local result = s:applyPeriodsToDay(
            { { 480, 1020, {} } },
            { positive = { { 600, 900, {} } }, negative = {} }
        )
        assertEq(#result, 3)
    end)
    test('Happy: только negative период', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        local result = s:applyPeriodsToDay(
            { { 480, 1020, {} } },
            { positive = {}, negative = { { 600, 900, {} } } }
        )
        assertDeepEq(result, { { 480, 600, {} }, { 900, 1020, {} } })
    end)
end)

describe('ScheduleRuntime — filterBefore (без мутаций)', function()
    test('возвращает клон, не меняя оригинал', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {},
        })
        -- 2024-01-11 00:00 = 28415520, pos = +900 минут (15:00)
        local dateTsm = 28415520
        local pos     = dateTsm + 900
        local original = {
            date_tsm  = dateTsm,
            intervals = { { 480, 720, {} }, { 780, 1020, {} } },
            schedule  = '08:00-12:00,13:00-17:00',
        }
        local filtered = s:filterBefore(original, pos)

        assertEq(#original.intervals, 2, 'оригинал не изменился')
        assertDeepEq(original.intervals[1], { 480, 720, {} })
        assertEq(filtered ~= original, true, 'клон отличается от оригинала')
        assertEq(#filtered.intervals, 1)
        assertDeepEq(filtered.intervals[1], { 780, 1020, {} })
    end)
    test('запись не на текущий день — оригинал', function()
        local s = ScheduleRuntime.new({
            main = { start_tsm = 0, end_tsm = nil, periods = {} },
            overrides = {},
        })
        local entry = { date_tsm = 28415520, intervals = { { 480, 1020, {} } } }
        local pos   = 28416960 + 600
        local res   = s:filterBefore(entry, pos)
        assertEq(#res.intervals, 1)
        assertEq(#entry.intervals, 1)
    end)
end)

describe('ScheduleRuntime — nextWorkingDateTime', function()
    local function baseSchedule()
        return {
            main = {
                name      = 'Офис',
                start_tsm = 28401120,
                end_tsm   = nil,
                default   = { intervals = { { 480, 1020, {} } } },
                weekdays  = { ['6'] = { intervals = {} }, ['7'] = { intervals = {} } },
                dates     = {},
                periods   = {},
            },
            overrides = {},
        }
    end

    test('Happy: внутри рабочего интервала возвращается то же время', function()
        local s = ScheduleRuntime.new(baseSchedule())
        assertEq(s:nextWorkingDateTime('2024-01-08 10:00'), '2024-01-08 10:00')
    end)
    test('Happy: вечер вт → утро ср', function()
        local s = ScheduleRuntime.new(baseSchedule())
        assertEq(s:nextWorkingDateTime('2024-01-09 20:00'), '2024-01-10 08:00')
    end)
    test('Happy: вечер пт → утро пн (через выходные)', function()
        local s = ScheduleRuntime.new(baseSchedule())
        assertEq(s:nextWorkingDateTime('2024-01-05 20:00'), '2024-01-08 08:00')
    end)
    test('Edge: до начала расписания → start расписания', function()
        local s = ScheduleRuntime.new(baseSchedule())
        assertEq(s:nextWorkingDateTime('2020-01-01 05:00'), '2024-01-01 08:00')
    end)
    test('Error: nil → nil', function()
        local s = ScheduleRuntime.new(baseSchedule())
        assertNil(s:nextWorkingDateTime(nil))
    end)
    test('Edge: positive period выбирается раньше дальнего weekday', function()
        -- В schedule только выходные дни недели + один positive период.
        -- positive period: 2024-01-10 10:00 .. 2024-01-12 22:59 ⇒ должен быть выбран как ближайшая работа.
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 28401120, end_tsm = nil,
                default   = nil,
                weekdays  = {
                    ['1'] = { intervals = {} }, ['2'] = { intervals = {} },
                    ['3'] = { intervals = {} }, ['4'] = { intervals = {} },
                    ['5'] = { intervals = {} }, ['6'] = { intervals = {} },
                    ['7'] = { intervals = {} },
                },
                dates   = {},
                periods = {
                    { start_tsm = 28414680, end_tsm = 28418139, is_work = true },
                },
            },
            overrides = {},
        })
        assertEq(s:nextWorkingDateTime('2024-01-06 10:00'), '2024-01-10 10:00')
    end)
    test('Edge: внутри отрицательного периода — после его окончания', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-02-01 16:00 — внутри non-work периода [28446430, 28448047).
        -- nextWorkingDateTime обязан вернуть время НЕ раньше end_tsm=28448047 (2024-02-02 14:07).
        local result    = s:nextWorkingDateTime('2024-02-01 16:00')
        assertNotNil(result)
        local resultTsm = strToTsm(result)
        assertGreater(resultTsm, 28448047 - 1)
    end)
end)

describe('ScheduleRuntime — nextWorkingMeta', function()
    test('Happy: meta пятницы', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- пятница 2024-01-05 09:00 — рабочее время → meta пятницы
        assertDeepEq(s:nextWorkingMeta('2024-01-05 09:00'), { user = 'pupkin' })
    end)
    test('Happy: до начала рабочего дня — meta следующего рабочего', function()
        local s = ScheduleRuntime.new(buildTestSchedule())
        -- 2024-01-05 03:00 → 2024-01-05 08:00 (пятница)
        assertDeepEq(s:nextWorkingMeta('2024-01-05 03:00'), { user = 'pupkin' })
    end)
end)

describe('ScheduleRuntime — Edge cases', function()
    test('расписание без periods', function()
        local s = ScheduleRuntime.new({
            main = {
                start_tsm = 28401120, end_tsm = nil,
                default   = { intervals = { { 480, 1020, {} } } },
                weekdays  = {}, dates = {}, periods = {},
            },
            overrides = {},
        })
        assertGreater(#s:getDateIntervals('2024-01-15'), 0)
    end)
end)

describe('приоритет дат/периодов main над override', function()
    local d1 = strToTsm('2024-07-01') -- внутри окна override
    local d2 = strToTsm('2024-07-02')
    local d3 = strToTsm('2024-07-03')
    local d4 = strToTsm('2024-07-04')
    local function build()
        return ScheduleRuntime.new({
            tz = 'UTC',
            main = {
                name = 'Main',
                start_tsm = strToTsm('2024-01-01'),
                end_tsm = nil,
                default = { schedule = '08:00-17:00', intervals = { { 480, 1020, {} } } },
                weekdays = {},
                dates = {
                    [tostring(d1)] = { date_tsm = d1, schedule = '-',           intervals = {} },
                    [tostring(d2)] = { date_tsm = d2, schedule = '10:00-12:00', intervals = { { 600, 720, {} } } },
                },
                periods = {
                    { start_tsm = d3 + 600, end_tsm = d3 + 720, is_work = false },
                },
            },
            overrides = {
                {
                    name = 'Лето',
                    start_tsm = strToTsm('2024-06-01'),
                    end_tsm   = strToTsm('2024-09-01'),
                    default = { schedule = '09:00-18:00', intervals = { { 540, 1080, {} } } },
                    weekdays = {},
                },
            },
        })
    end

    test('дата-исключение-выходной перебивает override', function()
        assertFalse(build():isWorkDay('2024-07-01'))
    end)
    test('дата-исключение с графиком перебивает override', function()
        assertDeepEq(build():getDateIntervals(d2), { { 600, 720, {} } })
    end)
    test('нерабочий период main вычитается поверх графика override', function()
        assertDeepEq(build():getDateIntervals(d3), { { 540, 600, {} }, { 720, 1080, {} } })
    end)
    test('день в окне override без исключений/периодов → график override', function()
        assertDeepEq(build():getDateIntervals(d4), { { 540, 1080, {} } })
    end)
    test('nextWorkingDateTime пропускает выходной-исключение в окне override', function()
        assertEq(build():nextWorkingDateTime('2024-07-01 00:00'), '2024-07-02 10:00')
    end)
end)

describe('открытые периоды (null-границы)', function()
    -- Регресс на баг: период без даты окончания (end_tsm == nil) в Lua ронял
    -- сравнение `nil > number` (runtime-ошибка), а по смыслу должен трактоваться
    -- как +inf (ещё длится). Симметрично проверяем открытый старт (start_tsm == nil).

    -- Расписание, заданное только периодами (без недельного графика).
    local function periodRuntime(periods)
        return ScheduleRuntime.new({
            main = {
                start_tsm = nil, end_tsm = nil,
                default = nil, weekdays = {}, dates = {},
                periods = periods,
            },
            overrides = {},
        })
    end

    -- Период с датами-строками; nil → открытая граница (start=-inf / end=+inf).
    local function period(startStr, endStr, isWork)
        return {
            start_tsm = startStr and strToTsm(startStr) or nil,
            end_tsm   = endStr and strToTsm(endStr) or nil,
            is_work   = isWork,
            meta      = {},
        }
    end

    test('открытый рабочий период активен после старта', function()
        local s = periodRuntime({ period('2023-01-01', nil, true) })
        assertTrue(s:isWorkTime('2024-06-15 12:00'), 'середина дня')
        assertTrue(s:isWorkTime('2024-06-15 00:00'), 'левая граница дня')
        assertTrue(s:isWorkDay('2024-06-15'))
        assertGreater(#s:getDatePeriods(strToTsm('2024-06-15')), 0)
    end)

    test('открытый рабочий период до старта → nextWorkingDateTime=старт', function()
        local s = periodRuntime({ period('2099-01-01', nil, true) })
        assertFalse(s:isWorkTime('2024-06-15 12:00'))
        assertEq(s:nextWorkingDateTime('2024-06-15 12:00'), '2099-01-01 00:00')
    end)

    test('открытый нерабочий период перекрывает доступ навсегда', function()
        local s = periodRuntime({ period('2023-01-01', nil, false) })
        assertFalse(s:isWorkTime('2024-06-15 12:00'))
        assertNil(s:nextWorkingDateTime('2024-06-15 12:00'))
    end)

    test('открытый слева период активен до даты конца', function()
        local s = periodRuntime({ period(nil, '2099-01-01', true) })
        assertTrue(s:isWorkTime('2024-06-15 12:00'))
        assertTrue(s:isWorkTime('1999-01-01 00:00'), 'нет нижней границы')
    end)

    test('полностью открытый рабочий период активен всегда', function()
        local s = periodRuntime({ period(nil, nil, true) })
        assertTrue(s:isWorkTime('2024-06-15 12:00'))
        assertTrue(s:isWorkTime('1999-01-01 00:00'))
    end)

    test('nextPeriod принимает открытый конец', function()
        local s = periodRuntime({ period('2023-01-01', nil, true) })
        assertNotNil(s:nextPeriod(strToTsm('2024-06-15 12:00'), true))
    end)

    test('nextWorkingDateTime внутри открытого периода = текущий момент', function()
        local s = periodRuntime({ period('2023-01-01', nil, true) })
        assertEq(s:nextWorkingDateTime('2024-06-15 12:00'), '2024-06-15 12:00')
    end)

    test('getDatePeriodsIntervals обрезает открытый конец по концу дня', function()
        local s   = periodRuntime({ period('2023-01-01', nil, true) })
        local res = s:getDatePeriodsIntervals(strToTsm('2024-06-15'))
        assertDeepEq(res.positive, { { 0, 1440, {} } })
        assertDeepEq(res.negative, {})
    end)
end)

-- =============================================================================
-- Запуск
-- =============================================================================

io.write('Tests: ' .. tostring(total) .. ', passed: ' .. tostring(passed)
    .. ', failed: ' .. tostring(failed) .. '\n')

if failed > 0 then
    io.write('\nFAILURES:\n')
    for _, f in ipairs(failures) do
        io.write('  X ' .. f .. '\n')
    end
    os.exit(1)
else
    io.write('All tests passed.\n')
end
