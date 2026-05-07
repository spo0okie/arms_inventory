--[[
=============================================================================
ScheduleRuntime — порт demo.js (lib/js/demo.js) на Lua 5.2
=============================================================================

Назначение:
  Работа со скомпилированными расписаниями (compiled_json) без БД.
  Все данные передаются как обычная Lua-таблица — структура совпадает с JSON
  (см. lib/js/demo.js, шапка файла; и compile/compile.md):

    {
      tz = '...',  tz_shift_tsm = 300,
      main = {
        name, start_tsm, end_tsm,
        default  = { intervals = { {480, 1020, {}}, ... } },
        weekdays = { ['1'] = { intervals = {...} }, ... },
        dates    = { ['28401120'] = { date_tsm = 28401120, intervals = {...} }, ... },
        periods  = { { start_tsm, end_tsm, is_work = true|false, comment, meta }, ... },
      },
      overrides = { { name, start_tsm, end_tsm, default, weekdays, dates, periods }, ... },
    }

Соглашения Lua-структуры:
  - intervals  — последовательная таблица 1-based: { {start, end, meta}, ... }
  - weekdays   — таблица со СТРОКОВЫМИ ключами '1'..'7' (как в JSON)
  - dates      — таблица со СТРОКОВЫМИ ключами date_tsm (как в JSON)
  - periods    — последовательная таблица, ОТСОРТИРОВАННАЯ по start_tsm

Все *_tsm значения хранятся в UTC-минутах (см. SchedulesCompiler::tzShiftMinutes).
Парсинг строк дат тоже происходит как UTC, без обращения к os.time/локали.
=============================================================================
]]

local M = {}

-- =============================================================================
-- Константы
-- =============================================================================

local MINUTES_IN_DAY = 1440

local function floor(x) return math.floor(x) end

-- =============================================================================
-- Календарь (Howard Hinnant): дни от Unix epoch без обращения к os.time
-- =============================================================================

local function days_from_civil(y, m, d)
    if m <= 2 then y = y - 1 end
    local era = floor(y / 400)
    local yoe = y - era * 400
    local mp  = (m > 2) and (m - 3) or (m + 9)
    local doy = floor((153 * mp + 2) / 5) + d - 1
    local doe = yoe * 365 + floor(yoe / 4) - floor(yoe / 100) + doy
    return era * 146097 + doe - 719468
end

local function civil_from_days(z)
    z = z + 719468
    local era = (z >= 0) and floor(z / 146097) or floor((z - 146096) / 146097)
    local doe = z - era * 146097
    local yoe = floor((doe - floor(doe / 1460) + floor(doe / 36524) - floor(doe / 146096)) / 365)
    local y   = yoe + era * 400
    local doy = doe - (365 * yoe + floor(yoe / 4) - floor(yoe / 100))
    local mp  = floor((5 * doy + 2) / 153)
    local d   = doy - floor((153 * mp + 2) / 5) + 1
    local m   = (mp < 10) and (mp + 3) or (mp - 9)
    if m <= 2 then y = y + 1 end
    return y, m, d
end

-- =============================================================================
-- Конвертеры строка ↔ tsm
-- =============================================================================

local function strToTsm(str)
    if str == nil or str == '' then return nil end
    if type(str) ~= 'string' then return nil end

    local y, mo, d, h, mi
    y, mo, d, h, mi = string.match(str, '^(%d%d%d%d)%-(%d%d)%-(%d%d) (%d%d):(%d%d)$')
    if not y then
        y, mo, d = string.match(str, '^(%d%d%d%d)%-(%d%d)%-(%d%d)$')
        if not y then return nil end
        h, mi = '00', '00'
    end

    y, mo, d = tonumber(y), tonumber(mo), tonumber(d)
    h, mi    = tonumber(h), tonumber(mi)
    if not (y and mo and d and h and mi) then return nil end
    if mo < 1 or mo > 12 or d < 1 or d > 31 or h < 0 or h > 23 or mi < 0 or mi > 59 then
        return nil
    end

    return days_from_civil(y, mo, d) * MINUTES_IN_DAY + h * 60 + mi
end

local function tsmToStr(tsm)
    if tsm == nil then return nil end
    local days = floor(tsm / MINUTES_IN_DAY)
    local rem  = tsm - days * MINUTES_IN_DAY
    local h    = floor(rem / 60)
    local mi   = rem - h * 60
    local y, mo, d = civil_from_days(days)
    return string.format('%04d-%02d-%02d %02d:%02d', y, mo, d, h, mi)
end

local function tsmToDateTsm(tsm)
    if tsm == nil then return nil end
    return floor(tsm / MINUTES_IN_DAY) * MINUTES_IN_DAY
end

-- 1=пн ... 7=вс. 1970-01-01 (epoch) — четверг (=4 в этой нотации).
local function dayOfWeek(tsm)
    if tsm == nil then return nil end
    local days = floor(tsm / MINUTES_IN_DAY)
    return ((days + 3) % 7) + 1
end

local function inBounds(tsm, bounds)
    if not bounds then return false end
    if tsm == nil then return false end
    if bounds.start_tsm ~= nil and tsm < bounds.start_tsm then return false end
    if bounds.end_tsm   ~= nil and tsm >= bounds.end_tsm  then return false end
    return true
end

-- =============================================================================
-- Базовые операции над интервалами
-- =============================================================================

local function intervalsContains(intervals, tsm)
    if not intervals or #intervals == 0 then return nil end
    local dayStart       = tsmToDateTsm(tsm)
    local minutesFromDay = tsm - dayStart
    for i = 1, #intervals do
        local iv = intervals[i]
        if minutesFromDay >= iv[1] and minutesFromDay < iv[2] then
            return iv
        end
    end
    return nil
end

local function shallowCopyArray(t)
    local r = {}
    for i = 1, #t do r[i] = t[i] end
    return r
end

local function intervalsSubtract(intervals, subtract)
    if not intervals or #intervals == 0 then return {} end
    if not subtract or not subtract[1] or not subtract[2] or subtract[2] <= subtract[1] then
        return shallowCopyArray(intervals)
    end

    local subStart, subEnd = subtract[1], subtract[2]
    local result = {}

    for i = 1, #intervals do
        local interval = intervals[i]
        local intStart, intEnd, meta = interval[1], interval[2], interval[3]

        if subEnd <= intStart or subStart >= intEnd then
            -- нет пересечения
            result[#result + 1] = interval
        elseif subStart <= intStart and subEnd >= intEnd then
            -- полностью покрывает — удаляем
        elseif subStart > intStart and subEnd >= intEnd then
            -- усекаем справа
            result[#result + 1] = { intStart, subStart, meta }
        elseif subStart <= intStart and subEnd < intEnd then
            -- усекаем слева
            result[#result + 1] = { subEnd, intEnd, meta }
        else
            -- разрезаем на две части (subStart > intStart and subEnd < intEnd)
            result[#result + 1] = { intStart, subStart, meta }
            result[#result + 1] = { subEnd,   intEnd,   meta }
        end
    end

    return result
end

local function intervalsAdd(intervals, override)
    -- intervals пустой → вернуть либо пусто, либо клон override
    if not intervals or #intervals == 0 then
        if not override or not override[1] or not override[2] or override[2] <= override[1] then
            return {}
        end
        return { { override[1], override[2], override[3] } }
    end

    -- override пустой/некорректный → вернуть копию intervals
    if not override or not override[1] or not override[2] or override[2] <= override[1] then
        return shallowCopyArray(intervals)
    end

    -- 1) освобождаем место под override
    local result = intervalsSubtract(intervals, override)

    -- 2) вставляем override, сохраняя сортировку по началу (без склейки)
    local overrideStart = override[1]
    local inserted      = false
    for i = 1, #result do
        if result[i][1] > overrideStart then
            table.insert(result, i, { override[1], override[2], override[3] })
            inserted = true
            break
        end
    end
    if not inserted then
        result[#result + 1] = { override[1], override[2], override[3] }
    end

    return result
end

-- =============================================================================
-- Хелпер: отсортированные ключи dates ('28401120', '28402560', ...)
-- =============================================================================

local function sortedDateKeys(t)
    local keys = {}
    for k, _ in pairs(t) do keys[#keys + 1] = k end
    table.sort(keys, function(a, b)
        return (tonumber(a) or 0) < (tonumber(b) or 0)
    end)
    return keys
end

-- =============================================================================
-- Класс ScheduleRuntime
-- =============================================================================

local ScheduleRuntime = {}
ScheduleRuntime.__index = ScheduleRuntime

function ScheduleRuntime.new(compiledJson)
    local self      = setmetatable({}, ScheduleRuntime)
    compiledJson    = compiledJson or {}
    self.schedule   = compiledJson
    self.main       = compiledJson.main or {}
    self.overrides  = compiledJson.overrides or {}
    return self
end

-- ----------------------------------------------------------------------------
-- ПУБЛИЧНЫЕ API МЕТОДЫ
-- ----------------------------------------------------------------------------

function ScheduleRuntime:isWorkDay(date)
    local tsm = strToTsm(date)
    if tsm == nil then return false end
    local intervals = self:getDateIntervals(tsm)
    return #intervals > 0
end

function ScheduleRuntime:isWorkTime(dateTime)
    local tsm = strToTsm(dateTime)
    if tsm == nil then return false end
    local intervals = self:getDateIntervals(tsm)
    return intervalsContains(intervals, tsm) ~= nil
end

function ScheduleRuntime:getMeta(dateTime)
    local tsm = strToTsm(dateTime)
    if tsm == nil then return nil end
    local intervals = self:getDateIntervals(tsm)
    local interval  = intervalsContains(intervals, tsm)
    if interval == nil then return nil end
    return interval[3] or nil
end

function ScheduleRuntime:nextWorkingDateTime(dateTime)
    local pos = strToTsm(dateTime)
    if pos == nil then return nil end

    if self.main.start_tsm ~= nil and pos < self.main.start_tsm then
        pos = self.main.start_tsm
    end

    while inBounds(pos, self.main) do
        local nonWork = self:findPeriod(pos, false)
        if nonWork ~= nil then
            -- перематываем pos на конец периода простоя
            pos = nonWork.end_tsm
        else
            local target = self:findOverride(pos)
            local entry  = self:nextRecord(pos, target)

            if entry == nil then
                if target.end_tsm ~= nil then
                    pos = target.end_tsm + 1
                else
                    return nil
                end
            elseif entry.type == 'period' then
                return tsmToStr(entry.start_tsm)
            elseif entry.type == 'override' then
                pos = entry.start_tsm
            else
                local dayStart  = entry.date_tsm or tsmToDateTsm(pos)
                local workStart = dayStart + entry.intervals[1][1]
                return tsmToStr(math.max(pos, workStart))
            end
        end
    end
    return nil
end

function ScheduleRuntime:nextWorkingMeta(dateTime)
    local nextWorktime = self:nextWorkingDateTime(dateTime)
    if nextWorktime == nil then return nil end

    local tsm     = strToTsm(dateTime)
    local nextTsm = strToTsm(nextWorktime)
    if nextTsm < tsm then
        return self:getMeta(tsmToStr(tsm))
    end
    return self:getMeta(nextWorktime)
end

-- ----------------------------------------------------------------------------
-- ВНУТРЕННИЕ МЕТОДЫ
-- ----------------------------------------------------------------------------

-- date может быть числом (tsm) либо строкой 'YYYY-MM-DD'/'YYYY-MM-DD HH:MM'.
-- Это отличие от JS-версии, которая корректно работает только с tsm-числом
-- (в JS-тестах строка проходит через NaN-арифметику и попадает в default override).
function ScheduleRuntime:getDateIntervals(date)
    if type(date) == 'string' then
        date = strToTsm(date)
        if date == nil then return {} end
    end

    local dateTsm = tsmToDateTsm(date)
    if dateTsm == nil then return {} end

    if not inBounds(dateTsm, self.main) then return {} end

    local target        = self:findOverride(dateTsm)
    local baseIntervals = self:getEntryIntervals(target, dateTsm)
    local periods       = self:getDatePeriodsIntervals(dateTsm)
    return self:applyPeriodsToDay(baseIntervals, periods)
end

function ScheduleRuntime:getDatePeriods(dateTsm)
    local dayStart = tsmToDateTsm(dateTsm)
    local dayEnd   = dayStart + MINUTES_IN_DAY
    local result   = {}
    local periods  = self.main.periods or {}
    for i = 1, #periods do
        local p = periods[i]
        if p.end_tsm > dayStart and p.start_tsm < dayEnd then
            result[#result + 1] = p
        end
    end
    return result
end

function ScheduleRuntime:getDatePeriodsIntervals(dateTsm)
    local periods  = self:getDatePeriods(dateTsm)
    local dayStart = tsmToDateTsm(dateTsm)
    local dayEnd   = dayStart + MINUTES_IN_DAY
    local positive, negative = {}, {}
    for i = 1, #periods do
        local p          = periods[i]
        local s          = math.max(p.start_tsm, dayStart)
        local e          = math.min(p.end_tsm, dayEnd)
        local interval   = { s - dayStart, e - dayStart, p.meta or {} }
        if p.is_work == true then
            positive[#positive + 1] = interval
        else
            negative[#negative + 1] = interval
        end
    end
    return { positive = positive, negative = negative }
end

function ScheduleRuntime:findOverride(tsm)
    for i = 1, #self.overrides do
        if inBounds(tsm, self.overrides[i]) then
            return self.overrides[i]
        end
    end
    return self.main
end

function ScheduleRuntime:nextOverride(tsm)
    -- ГАРАНТИЯ: overrides отсортированы по start_tsm при компиляции.
    for i = 1, #self.overrides do
        if self.overrides[i].start_tsm >= tsm then
            return self.overrides[i]
        end
    end
    return nil
end

function ScheduleRuntime:findPeriod(tsm, isWork)
    local periods = self.main.periods or {}
    for i = 1, #periods do
        local p = periods[i]
        if inBounds(tsm, p) and (isWork == nil or isWork == p.is_work) then
            return p
        end
    end
    return nil
end

-- Строгая семантика end_tsm > tsm: период [start, end) с end == tsm уже закончился.
function ScheduleRuntime:nextPeriod(tsm, isWork)
    local periods = self.main.periods or {}
    for i = 1, #periods do
        local p = periods[i]
        if p.end_tsm > tsm and (isWork == nil or isWork == p.is_work) then
            return p
        end
    end
    return nil
end

function ScheduleRuntime:getEntryIntervals(target, dateTsm)
    local key = tostring(dateTsm)
    if target.dates and target.dates[key] then
        return target.dates[key].intervals or {}
    end

    local dowKey = tostring(dayOfWeek(dateTsm))
    if target.weekdays and target.weekdays[dowKey] then
        return target.weekdays[dowKey].intervals or {}
    end

    if target.default then
        return target.default.intervals or {}
    end
    return {}
end

-- Не мутирует оригинал — возвращает клон со списком неистёкших интервалов.
function ScheduleRuntime:filterBefore(entry, tsm)
    local tsmDate = tsmToDateTsm(tsm)
    if entry.date_tsm ~= tsmDate then return entry end

    local minutesFromDay = tsm - tsmDate
    local filtered       = {}
    for i = 1, #entry.intervals do
        if entry.intervals[i][2] > minutesFromDay then
            filtered[#filtered + 1] = entry.intervals[i]
        end
    end

    local clone = {}
    for k, v in pairs(entry) do clone[k] = v end
    clone.intervals = filtered
    return clone
end

function ScheduleRuntime:nextWorkDateEntry(tsm, target)
    if not target.dates then return nil end

    local keys = sortedDateKeys(target.dates)
    for i = 1, #keys do
        local entry     = target.dates[keys[i]]
        local dateTsm   = tonumber(keys[i])
        local dayEndTsm = dateTsm + MINUTES_IN_DAY

        if entry.intervals and #entry.intervals > 0 and dayEndTsm > tsm then
            local filtered = self:filterBefore(entry, tsm)
            if #filtered.intervals > 0 then
                return filtered
            end
        end
    end
    return nil
end

function ScheduleRuntime:nextWeekDayEntry(pos, target)
    local dow      = dayOfWeek(pos)
    local dayStart = tsmToDateTsm(pos)

    for i = 0, 6 do
        local checkDay = ((dow + i - 1) % 7) + 1
        local weekday  = (target.weekdays and target.weekdays[tostring(checkDay)]) or target.default
        local tsmDate  = dayStart + i * MINUTES_IN_DAY

        if weekday and weekday.intervals and #weekday.intervals > 0 then
            local entry = {}
            for k, v in pairs(weekday) do entry[k] = v end
            entry.date_tsm = tsmDate

            local filtered = self:filterBefore(entry, pos)
            if #filtered.intervals > 0 then
                return filtered
            end
        end
    end
    return nil
end

function ScheduleRuntime:applyPeriodsToDay(baseIntervals, periods)
    local intervals = shallowCopyArray(baseIntervals)
    for i = 1, #periods.negative do
        intervals = intervalsSubtract(intervals, periods.negative[i])
    end
    for i = 1, #periods.positive do
        intervals = intervalsAdd(intervals, periods.positive[i])
    end
    return intervals
end

function ScheduleRuntime:nextRecord(pos, target)
    local isMain     = (target == self.main)
    local candidates = {}

    local period = self:nextPeriod(pos, true)
    if period then
        local p = {}
        for k, v in pairs(period) do p[k] = v end
        p.type = 'period'
        candidates[#candidates + 1] = p
    end

    local weekEntry = self:nextWeekDayEntry(pos, target)
    if weekEntry then
        weekEntry.type      = 'weekday'
        weekEntry.start_tsm = weekEntry.date_tsm
        candidates[#candidates + 1] = weekEntry
    end

    if isMain then
        local override = self:nextOverride(pos)
        if override then
            local o = {}
            for k, v in pairs(override) do o[k] = v end
            o.type = 'override'
            candidates[#candidates + 1] = o
        end

        local dateEntry = self:nextWorkDateEntry(pos, target)
        if dateEntry then
            dateEntry.type      = 'date'
            dateEntry.start_tsm = dateEntry.date_tsm
            candidates[#candidates + 1] = dateEntry
        end
    end

    if #candidates == 0 then return nil end

    table.sort(candidates, function(a, b) return a.start_tsm < b.start_tsm end)
    return candidates[1]
end

-- =============================================================================
-- Экспорт
-- =============================================================================

M.ScheduleRuntime    = ScheduleRuntime
M.strToTsm           = strToTsm
M.tsmToStr           = tsmToStr
M.tsmToDateTsm       = tsmToDateTsm
M.dayOfWeek          = dayOfWeek
M.inBounds           = inBounds
M.intervalsContains  = intervalsContains
M.intervalsSubtract  = intervalsSubtract
M.intervalsAdd       = intervalsAdd
M.MINUTES_IN_DAY     = MINUTES_IN_DAY

return M
