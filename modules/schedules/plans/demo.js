/**
 * =============================================================================
 * ScheduleRuntime — Демо-библиотека для работы с компилированными расписаниями
 * =============================================================================
 * 
 * Назначение:
 * Библиотека предназначена для работы со скомпилированными расписаниями (compiled_json)
 * без обращения к базе данных. Все данные хранятся в плоской структуре JSON.
 * 
 * Использование:
 * const schedule = new ScheduleRuntime(compiledJson);
 * const isWork = schedule.isWorkDay('2024-01-15');
 * const isWorkTime = schedule.isWorkTime('2024-01-15 10:00');
 * 
 * =============================================================================
 * Структура скомпилированного расписания (compiled_json)
 * =============================================================================
 * {
 *   "tz": "Asia/Yekaterinburg",           // Часовой пояс
 *   "tz_shift_tsm": 300,                  // Смещение часового пояса в минутах от UTC
 *   "compiled": "2024-01-15T10:30:00Z",   // Дата компиляции
 *   "main": {                             // Основное расписание
 *     "name": "График работы офиса",
 *     "start": "2024-01-01",              // Начало действия (строка для отладки)
 *     "start_tsm": 28401120,              // Начало действия (timestamp in minutes)
 *     "end": null,                         // Конец действия (null = бесконечно)
 *     "end_tsm": null,
 *     "default": {                         // Расписание по умолчанию
 *       "schedule": "08:00-17:00",
 *       "intervals": [[480, 1020, {}]]
 *     },
 *     "weekdays": {                        // Расписание по дням недели (1=пн, 7=вс)
 *       "1": { "schedule": "08:00-17:30", "intervals": [[480, 1050, {}]], "comment": "Понедельник" },
 *       "5": { "schedule": "08:00-16:00", "intervals": [[480, 960, {user:"pupkin"}]], "comment": "Пятница" },
 *       "6": { "schedule": "-", "intervals": [] },   // Суббота - выходной
 *       "7": { "schedule": "-", "intervals": [] }    // Воскресенье - выходной
 *     },
 *     "dates": {                           // Исключения по конкретным датам (индекс по date_tsm строкой)
 *       // Ключи — строки (т.к. JSON), значения — date_tsm числа
 *       "28401120": { "date_tsm": 28401120, "schedule": "-", "intervals": [], "comment": "Новый год" },
 *       "28402560": { "date_tsm": 28402560, "schedule": "10:00-15:00", "intervals": [[600, 900, {}]] }
 *     },
 *     "periods": [                         // Особые периоды (работа в праздники, простой)
 *       { "start": "2024-01-10 10:00", "start_tsm": 28414680, "end": "2024-01-12 22:59", "end_tsm": 28418139, 
 *         "is_work": true, "comment": "Работали непрерывно" },
 *       { "start": "2024-02-01 15:10", "start_tsm": 28446430, "end": "2024-02-02 18:17", "end_tsm": 28448047, 
 *         "is_work": false, "comment": "Аварийное отключение" }
 *     ]
 *   },
 *   "overrides": [                         // Перекрытия расписания (сезонные графики)
 *     {
 *       "name": "Лето 2024",
 *       "start": "2024-06-01",
 *       "start_tsm": 28620000,
 *       "end": "2024-08-31",
 *       "end_tsm": 28752400,
 *       "default": { "schedule": "09:00-18:00", "intervals": [[540, 1080, {}]] },
 *       "weekdays": {},
 *       "dates": {},
 *       "periods": []
 *     }
 *   ]
 * }
 * 
 * =============================================================================
 * Терминология
 * =============================================================================
 * - tsm (timestamp in minutes) — количество минут от Unix epoch (01.01.1970 00:00 UTC)
 *   Пример: 2024-01-01 00:00:00 UTC → 1704067200 сек → 28401120 минут
 * 
 * - date_tsm — timestamp начала дня (время обнулено до 00:00)
 * 
 * - interval — [start_minute, end_minute, {meta}]
 *   Пример: [480, 1020, {duty: "Иванов"}] = 08:00-17:00 с метаданными
 *   minutes от начала дня: 480 = 08:00, 1020 = 17:00
 * 
 * - period — особый период (работа в праздник, простой)
 *   { start_tsm, end_tsm, is_work: true|false, comment, meta }
 * 
 * - override — перекрытие расписания (сезонный график, отпуск)
 * 
 * =============================================================================
 * Основные понятия
 * =============================================================================
 * 1. Рабочий день — день, в котором есть хотя бы один рабочий интервал
 * 2. Рабочее время — время, входящее в рабочий интервал дня
 * 3. Период (period) — непрерывный период работы или простоя
 *    - is_work=true: добавляет рабочее время
 *    - is_work=false: удаляет рабочее время
 * 4. Override — полная замена расписания на период времени
 * 
 * =============================================================================
 */

/**
 * Константы
 */
const MINUTES_IN_DAY = 1440;          // Минут в дне (24 * 60)
const MS_IN_MINUTE = 60000;          // Миллисекунд в минуте

/**
 * ScheduleRuntime — класс для работы с компилированным расписанием
 * 
 * Основные методы:
 * - isWorkDay(date) — рабочий ли день
 * - isWorkTime(dateTime) — рабочее ли время
 * - getMeta(dateTime) — метаданные на время
 * - nextWorkingDateTime(dateTime) — ближайшее рабочее время
 * - nextWorkingMeta(dateTime) — метаданные на ближайшее рабочее время
 */
class ScheduleRuntime {

	/**
     * Конструктор
     * @param {Object} compiledJson - Скомпилированное расписание из поля compiled_json
     */
    constructor(compiledJson) {
        this.schedule = compiledJson;
        
        // Основное расписание (main)
        this.main = compiledJson.main || {};
        
        // Перекрытия (overrides) — массив объектов с той же структурой, что и main
        this.overrides = compiledJson.overrides || [];
    }

    // ==========================================================================
    // ПУБЛИЧНЫЕ API МЕТОДЫ
    // ==========================================================================

    /**
     * isWorkDay — проверка рабочего дня на дату
     * 
     * Возвращает true, если на указанную дату есть хотя бы один рабочий интервал.
     * 
     * @param {string} date - Дата в формате 'YYYY-MM-DD' или 'YYYY-MM-DD HH:MM'
     * @returns {boolean} true если рабочий день, false если выходной
     * 
     * Примеры:
     * schedule.isWorkDay('2024-01-15')  // true/false
     * schedule.isWorkDay('2024-01-15 10:00') // true/false (время игнорируется)
     */
    isWorkDay(date) {
        const intervals = this.getDateIntervals(date);
        return intervals.length > 0;
    }

    /**
     * isWorkTime — проверка рабочего времени на дату-время
     * 
     * Возвращает true, если указанное время попадает в рабочий интервал дня.
     * 
     * @param {string} dateTime - Дата и время в формате 'YYYY-MM-DD HH:MM'
     * @returns {boolean} true если рабочее время, false если нет
     * 
     * Примеры:
     * schedule.isWorkTime('2024-01-15 10:00')  // true (рабочее время)
     * schedule.isWorkTime('2024-01-15 18:00')  // false (после работы)
     * schedule.isWorkTime('2024-01-15 07:00')  // false (до работы)
     */
    isWorkTime(dateTime) {
        // Парсим дату-время
        const tsm = strToTsm(dateTime);
        if (tsm === null) return false;
        
        // Получаем интервалы на дату
        const intervals = this.getDateIntervals(tsm);
        
        // Ищем интервал, содержащий это время
        const interval = intervalsContains(intervals, tsm);
        
        return interval !== null;
    }

    /**
     * getMeta — получение метаданных на дату-время
     * 
     * Возвращает метаданные (объект), привязанные к интервалу, содержащему указанное время.
     * Если время не в рабочем интервале или метаданных нет — возвращает null.
     * 
     * @param {string} dateTime - Дата и время в формате 'YYYY-MM-DD HH:MM'
     * @returns {Object|null} Объект метаданных или null
     * 
     * Примеры:
     * schedule.getMeta('2024-01-15 10:00')  // {duty: "Иванов"} или null
     */
    getMeta(dateTime) {
        const tsm = strToTsm(dateTime);
        if (tsm === null) return null;
        
        // Получаем интервалы на дату
        const intervals = this.getDateIntervals(tsm);
        
        // Ищем интервал, содержащий это время
        const interval = intervalsContains(intervals, tsm);
        
        if (interval === null) {
            return null;
        }
        
        // interval[2] — метаданные (третий элемент)
        return interval[2] || null;
    }

    /**
     * nextWorkingDateTime — ближайшее рабочее дата-время
     * 
     * Возвращает ближайшее рабочее время, начиная от указанной даты-времени.
     * Если переданное время уже рабочее — возвращает его же.
     * 
     * @param {string} dateTime - Дата и время в формате 'YYYY-MM-DD HH:MM'
     * @returns {string|null} Ближайшее рабочее время в формате 'YYYY-MM-DD HH:MM' или null
     * 
     * Примеры:
     * schedule.nextWorkingDateTime('2024-01-15 10:00')  // '2024-01-15 10:00' (уже рабочее)
     * schedule.nextWorkingDateTime('2024-01-15 18:00')  // '2024-01-16 08:00' (следующий день)
     */
    nextWorkingDateTime(dateTime) {
        let pos = strToTsm(dateTime);
        if (pos === null) return null;
        
        // Если переданное время раньше начала расписания — встаем в начало
        if (this.main.start_tsm !== null && pos < this.main.start_tsm) {
            pos = this.main.start_tsm;
        }
        
        // Цикл поиска следующего рабочего времени
		// pos будет двигаться только вправо 
		// пока не найдет рабочее время
		// пока не поймет что рабочего времени больше нет
		// или пока не выйдет за границы расписания
        while (inBounds(pos, this.main)) {
            
            
            // Ищем нерабочий период (is_work=false), перекрывающий текущую позицию
            const nonWorkPeriod = this.findPeriod(pos, false);
            
            if (nonWorkPeriod !== null) {
                // Если нашли период простоя — перематываем pos на конец периода
                pos = nonWorkPeriod.end_tsm;
                continue;
            }
            
            // Выбираем расписание (main или override) для текущей позиции
            const target = this.findOverride(pos);
            
            // Ищем ближайшую рабочую запись в выбранном расписании
            const entry = this.nextRecord(pos, target);
            
            if (entry === null) {
                // Нет рабочих записей — пропускаем расписание и ищем дальше
                if (target.end_tsm !== null) {
                    pos = target.end_tsm + 1;
                } else {
                    return null; // Бесконечное расписание без рабочих записей
                }
                continue;
            }
            
            // Определяем тип записи и возвращаем результат
            if (entry.type === 'period') {
                // Это период работы — возвращаем его начало
                return tsmToStr(entry.start_tsm);
            } else if (entry.type === 'override') {
                // Нашли override — продолжаем поиск от его начала
                pos = entry.start_tsm;
                continue;
            } else {
                // Это обычная запись (weekday/date) — возвращаем начало первого интервала
                const dayStart = tsmToDateTsm(pos);
                return tsmToStr(dayStart + entry.intervals[0][0]);
            }
        }
		return null; // Вышли за границы расписания, не найдя рабочее время
    }

    /**
     * nextWorkingMeta — метаданные ближайшего рабочего времени
     * 
     * Возвращает метаданные на ближайшее рабочее время.
     * 
     * @param {string} dateTime - Дата и время в формате 'YYYY-MM-DD HH:MM'
     * @returns {Object|null} Объект метаданных или null
     */
    nextWorkingMeta(dateTime) {
        const nextWorktime = this.nextWorkingDateTime(dateTime);
        
        if (nextWorktime === null) {
            return null;
        }
        
        // Если ближайшее рабочее раньше переданного — используем переданное
        const tsm = strToTsm(dateTime);
        const nextTsm = strToTsm(nextWorktime);
        
        if (nextTsm < tsm) {
            return this.getMeta(tsmToStr(tsm));
        }
        
        return this.getMeta(nextWorktime);
    }

    // ==========================================================================
    // ВНУТРЕННИЕ МЕТОДЫ
    // ==========================================================================

    /**
     * getDateIntervals — получить интервалы расписания на дату
     * 
     * Основной метод получения рабочих интервалов на день.
     * Выполняет:
     * 1. Проверку границ расписания
     * 2. Выбор override или main
     * 3. Поиск записи по дате (dates) или дню недели (weekdays) или default
     * 4. Применение периодов (periods) — добавление/удаление рабочего времени
     * 
     * @param {number} date - Дата в формате tsm
     * @returns {Array} Массив интервалов [[start, end, meta], ...]
     */
    getDateIntervals(date) {
        // Конвертируем в tsm начала дня
        const dateTsm = tsmToDateTsm(date);
        
		// Если дата невалидная — возвращаем пустой массив
        if (dateTsm === null) {
            return [];
        }
        
        // 1. Проверяем, попадает ли дата в границы основного расписания
        if (!inBounds(dateTsm, this.main)) {
            return []; // Дата вне границ расписания
        }
        
        // 2. Выбираем расписание (main или override)
        const target = this.findOverride(dateTsm);
        
        // 3. Получаем интервалы из записи (dates → weekdays → default)
        const baseIntervals = this.getEntryIntervals(target, dateTsm);
        
        // 4. Получаем периоды, перекрывающие дату
        const periods = this.getDatePeriodsIntervals(dateTsm);
        
        // 5. Применяем периоды к интервалам
        const result = this.applyPeriodsToDay(baseIntervals, periods);
        
        return result;
    }

    /**
     * getDatePeriods — получить периоды, попадающие в дату
     * 
     * Возвращает периоды (periods) из main, которые перекрывают указанный день.
     * Периоды существуют только в main, overrides содержат только недельный график.
     * Период перекрывает день, если:
     * - заканчивается НЕ РАНЬШЕ начала дня (end_tsm >= dayStart)
     * - начинается РАНЬШЕ конца дня (start_tsm < dayEnd)
     * 
     * @param {number} dateTsm - tsm начала дня
     * @returns {Array} Массив периодов
     */
    getDatePeriods(dateTsm) {
        // Вычисляем границы дня
        const dayStart = tsmToDateTsm(dateTsm);
        const dayEnd = dayStart + MINUTES_IN_DAY;
        
        const result = [];
        
        // Периоды существуют ТОЛЬКО в main
        const periods = this.main.periods || [];
        
        // Фильтруем периоды, перекрывающие день
        for (const period of periods) {
            // Период попадает в день, если:
            // - заканчивается НЕ РАНЬШЕ начала дня (end_tsm >= dayStart)
            // ИЛИ
            // - начинается РАНЬШЕ конца дня (start_tsm < dayEnd)
            if (period.end_tsm >= dayStart || period.start_tsm < dayEnd) {
                result.push(period);
            }
        }
        
        return result;
    }

    /**
     * getDatePeriodsIntervals — получить интервалы периодов, перекрывающих дату
     * 
     * Возвращает набор интервалов в пределах дня dateTsm, полученных от
     * перекрывающих день периодов.
     * 
     * @param {number} dateTsm - tsm начала дня
     * @returns {Object} { positive: [], negative: [] }
     */
    getDatePeriodsIntervals(dateTsm) {
        const periods = this.getDatePeriods(dateTsm);
        
        const dayStart = tsmToDateTsm(dateTsm);
        const dayEnd = dayStart + MINUTES_IN_DAY;
        
        const positive = [];
        const negative = [];
        
        for (const period of periods) {
            // Обрезаем период по границам дня
            const intervalStart = Math.max(period.start_tsm, dayStart);
            const intervalEnd = Math.min(period.end_tsm, dayEnd);
            
            // Конвертируем в минуты от начала дня
            const startMinute = intervalStart - dayStart;
            const endMinute = intervalEnd - dayStart;
            
            const interval = [startMinute, endMinute, period.meta || {}];
            
            if (period.is_work === true) {
                positive.push(interval);
            } else {
                negative.push(interval);
            }
        }
        
        return { positive, negative };
    }

    /**
     * findOverride — найти перекрытие расписания на дату/время
     * 
     * Ищет override, который перекрывает указанное время.
     * Если override не найден — возвращает main.
     * 
     * @param {number} tsm - timestamp in minutes
     * @returns {Object} main или override
     */
    findOverride(tsm) {
        for (const override of this.overrides) {
            if (inBounds(tsm, override)) {
                return override;
            }
        }
        return this.main;
    }

    /**
     * nextOverride — найти ближайший override, заканчивающийся не ранее tsm
     * 
     * Ищет первый override с end_tsm >= tsm.
     * ГАРАНТИЯ: overrides отсортированы по end_tsm при компиляции.
     * 
     * @param {number} tsm - timestamp in minutes
     * @returns {Object|null} { type: 'override', start_tsm, end_tsm, intervals } или null
     */
    nextOverride(tsm) {
        // Перебор отсортированного массива, первый подходящий — искомый
        for (const override of this.overrides) {
            if (override.end_tsm >= tsm) {
                return override;
            }
        }
        return null;
    }


    /**
     * findPeriod — найти период непрерывной работы/простоя на дату-время
     * 
     * Периоды существуют только в main, overrides содержат только недельный график.
     * 
     * @param {number} tsm - timestamp in minutes
     * @param {boolean|null} isWork - true=ищем work, false=ищем non-work, null=любой
     * @returns {Object|null} Период или null
     */
    findPeriod(tsm, isWork = null) {
        // Периоды существуют ТОЛЬКО в main
        for (const period of this.main.periods || []) {
            // Проверяем пересечение времени с периодом и совпадение по типу (is_work)           
            if (inBounds(tsm, period) && (isWork === null || isWork === period.is_work)) {
                return period;
            }
        }
        return null;
    }

    /**
     * nextPeriod — ближайший период непрерывной работы/простоя заканчивющийся не ранее tsm
     * 
     * Периоды существуют только в main, overrides содержат только недельный график.
     * 
     * @param {number} tsm - timestamp in minutes
     * @param {boolean|null} isWork - true=ищем work, false=ищем non-work, null=любой
     * @returns {Object|null} Период или null
     */
    nextPeriod(tsm, isWork = null) {
        // Периоды существуют ТОЛЬКО в main
        for (const period of this.main.periods || []) {
            // Проверяем пересечение времени с периодом и совпадение по типу (is_work)           
            if (period.end_tsm >= tsm && (isWork === null || isWork === period.is_work)) {
                return period;
            }
        }
        return null;
    }

	/**
     * getEntryIntervals — получить интервалы расписания из записи на дату
     * 
     * Ищет интервалы в порядке: dates → weekdays → default (periods/overrides не проверяются)
     * dates индексируется по date_tsm (строковый ключ в JSON)
     * 
     * @param {Object} target - main или override
     * @param {number} dateTsm - tsm начала дня
     * @returns {Array} Массив интервалов
     */
    getEntryIntervals(target, dateTsm) {
        // JSON ключи — строки, поэтому преобразуем number в string
        // dates: { "28401120": {...}, "28402560": {...} }
        const dateTsmKey = String(dateTsm);
        
        // 1. Ищем в dates (конкретная дата) — ключ dateTsm
        if (target.dates && target.dates[dateTsmKey]) {
            return target.dates[dateTsmKey].intervals || [];
        }
        
        // 2. Ищем в weekdays (день недели)
        const dayOfWeekNum = String(dayOfWeek(dateTsm)); // 1=пн, 7=вс
        if (target.weekdays && target.weekdays[dayOfWeekNum]) {
            return target.weekdays[dayOfWeekNum].intervals || [];
        }
        
        // 3. Используем default
        if (target.default) {
            return target.default.intervals || [];
        }
        
        return [];
    }

    /**
     * filterBefore — отфильтровать интервалы записи, оставив только те, которые ещё не завершились
     * 
     * Для заданного момента времени tsm фильтрует интервалы записи, оставляя только те,
     * которые ещё не завершились полностью к моменту tsm.
     * 
     * Если запись не на текущий день (entry.date_tsm !== tsmToDateTsm(tsm)), возвращается оригинал.
     * Интервал считается завершённым, если его конец <= минутам от начала дня tsm.
     * Граница: t >= start && t < end (end исключён)
     * 
     * @param {Object} entry - запись { start_tsm, intervals, ... }
     * @param {number} tsm - timestamp in minutes
     * @returns {Object} клон записи с отфильтрованными интервалами
     */
    filterBefore(entry, tsm) {
        const tsmDate = tsmToDateTsm(tsm);
        
        // Если запись не на текущий день — возвращаем оригинал
        if (entry.date_tsm !== tsmDate) {
            return entry;
        }
        
        // Вычисляем минуты от начала дня
        const minutesFromDay = tsm - tsmDate;
        
        // Фильтруем интервалы
        const filteredIntervals = entry.intervals.filter(int => int[1] > minutesFromDay);
        
        // Возвращаем клон с отфильтрованными интервалами
        return {
            ...entry,
            intervals: filteredIntervals
        };
    }

	/**
     * nextWorkDateEntry — найти ближайшую запись на дату с рабочими интервалами заканчивающимися не ранее tsm.
     * 
     * Ищет в target.dates первую запись с интервалами, которые заканчиваются не ранее tsm.
     * ГАРАНТИЯ: target.dates отсортирован по ключу (date_tsm) при компиляции.
     * 
     * Особенности обработки текущего дня:
     * - Если date_tsm совпадает с датой tsm (текущий день), необходимо отфильтровать интервалы,
     *   которые уже завершились к моменту tsm
     * - Если все интервалы отфильтрованы — дата пропускается
     * - Возвращается клон записи с отфильтрованными интервалами (мутация оригинала недопустима)
     * 
     * @param {number} tsm - timestamp in minutes
     * @param {Object} target - main или override
     * @returns {Object|null} { type: 'date', start_tsm, intervals } или null
     */
    nextWorkDateEntry(tsm, target) {
        if (!target.dates) {
            return null;
        }
        
        // Перебор отсортированных дат, первый подходящий — искомый
        for (const [dateTsmStr, entry] of Object.entries(target.dates)) {
            const dateTsm = parseInt(dateTsmStr);
            const dayEndTsm = dateTsm + MINUTES_IN_DAY;
            
            // Проверяем: есть интервалы И день заканчивается правее tsm
            if (entry.intervals?.length > 0 && dayEndTsm > tsm) {
                // Применяем filterBefore — он автоматически проверит дату и отфильтрует
                const filteredEntry = this.filterBefore(entry, tsm);
                
                // Если после фильтрации что-то осталось, то такая запись нам подходит
                if (filteredEntry.intervals.length > 0) {
	                return filteredEntry;
                }                
            }
        }
        
        return null;
    }

	/**
     * nextWeekDayEntry — найти ближайшую запись в weekdays/default с рабочими интервалами заканчивающимися не ранее tsm
     * 
     * Вспомогательный метод для firstEntry.
     * 
     * @param {number} pos - Позиция для поиска (tsm)
     * @param {Object} target - main или override
     * @returns {Object|null} Запись или null
     */
    nextWeekDayEntry(pos, target) {
        const dayOfWeekNum = dayOfWeek(pos);
        const dayStart = tsmToDateTsm(pos);
        
        // Проверяем 7 дней недели (дальше все повторяется и смысла искать нет)
        for (let i = 0; i < 7; i++) {
            const checkDay = (dayOfWeekNum + i - 1) % 7 + 1;
            const weekday = target.weekdays?.[String(checkDay)] || target.default;
			const tsmDate = dayStart + (i * MINUTES_IN_DAY);
            
            if (weekday?.intervals?.length > 0) {
				//вставляем date_tsm конвертируя день недели в рабочий график на конкретную дату
				//это нужно для дальнейшего анализа какая запись действует раньше по времени
				const entry = {...weekday, date_tsm: tsmDate};
                
                // Применяем filterBefore — он автоматически проверит дату и отфильтрует уже завершенные интервалы
                const filteredEntry = this.filterBefore(entry, pos);
                
                if (filteredEntry.intervals.length > 0) {
                    return filteredEntry;
                }
            }
        }
        
        return null;
    }


    /**
     * applyPeriodsToDay — наложить периоды на интервалы дня
     * 
     * Логика:
     * 1. positive-периоды (is_work=true) добавляют рабочее время
     *    - их meta должна заменять meta базового интервала при пересечении
     * 2. negative-периоды (is_work=false) удаляют рабочее время
     * 3. Результат объединяется с сохранением meta от периодов
     * 
     * @param {Array} baseIntervals - Базовые интервалы из расписания [[start, end, meta], ...]
     * @param {Object} periods - { positive: [[start, end, meta], ...], negative: [[start, end, meta], ...] }
     * @returns {Array} Результирующие интервалы
     */
    applyPeriodsToDay(baseIntervals, periods) {
        // Начинаем с копии базовых интервалов
        let intervals = [...baseIntervals];
        
        // 1. Сначала применяем negative-периоды (is_work=false) - вычитаем из интервалов
        for (const neg of periods.negative) {
            intervals = intervalsSubtract(intervals, neg);
        }
        
        // 2. Затем применяем positive-периоды (is_work=true)
        // При пересечении: period имеет приоритет, его meta заменяет интервальный
        for (const pos of periods.positive) {
            intervals = intervalsAdd(intervals, pos);
        }
        
        return intervals;
    }


    /**
     * nextRecord — найти ближайшую рабочую запись
     * 
     * Периоды (periods) существуют только в main.
     * ГАРАНТИЯ: overrides и dates отсортированы при компиляции.
     * Периоды проверяются перебором (необходимо отсортировать при компиляции).
     * 
     * @param {number} pos - Позиция для поиска (tsm)
     * @param {Object} target - main или override
     * @returns {Object|null} Запись или null
     */
    nextRecord(pos, target) {
        // Проверяем, ищем в main или override
        const isMain = (target === this.main);

		//из чего будем выбирать ближайшую запись
		candidates = [];

		//ищем ближайший рабочий период
		const period = this.nextPeriod(pos, true);
		if (period) {
			period.type = 'period';
			candidates.push(period);
		}

		//ближайшая запись на недельный график
		const weekEntry = this.nextWeekDayEntry(pos, target);
		if (weekEntry) {
			weekEntry.type = 'weekday';
			weekEntry.start_tsm = weekEntry.date_tsm; // для сравнения с периодами и override
			candidates.push(weekEntry);
		}		
              
        // Для main: ищем также записи которые бывают только в нем: override, dates
        if (isMain) {
            // следующее расписание-перекрытие
            const override = this.nextOverride(pos);
			if (override) {
				override.type = 'override';
				candidates.push(override);
			}

			// следующая запись по конкретной дате
            const dateEntry = this.nextWorkDateEntry(pos, target);
			if (dateEntry) {
				dateEntry.type = 'date';
				dateEntry.start_tsm = dateEntry.date_tsm; // для сравнения с периодами и override
				candidates.push(dateEntry);
			}
        }

		if (candidates.length === 0) {
			// ничего рабочего уже дальше не будет. мы нашли конец времен
			return null;
		}

		candidates.sort((a, b) => a.start_tsm - b.start_tsm);

        return candidates[0];
    }
}
    
// =============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (могут быть вынесены в отдельный модуль)
// =============================================================================

/**
 * strToTsm — конвертировать строку даты/времени в tsm
 * 
 * @param {string} str - 'YYYY-MM-DD' или 'YYYY-MM-DD HH:MM'
 * @returns {number|null} timestamp in minutes или null при ошибке
 * 
 * Примеры:
 * strToTsm('2024-01-01')        // 28401120
 * strToTsm('2024-01-01 10:30')  // 28401750
 */
function strToTsm(str) {
    if (!str) return null;
    
    // Добавляем время 00:00 если не указано
    if (str.length === 10) {
        str += ' 00:00';
    }
    
    const date = new Date(str.replace(' ', 'T'));
    if (isNaN(date.getTime())) return null;
    
    // Конвертируем в минуты от epoch
    return Math.floor(date.getTime() / MS_IN_MINUTE);
}

/**
 * tsmToStr — конвертировать tsm в строку даты-времени
 * 
 * @param {number} tsm - timestamp in minutes
 * @returns {string} 'YYYY-MM-DD HH:MM'
 */
function tsmToStr(tsm) {
    if (tsm === null || tsm === undefined) return null;
    
    const date = new Date(tsm * MS_IN_MINUTE);
    const year = date.getUTCFullYear();
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const day = String(date.getUTCDate()).padStart(2, '0');
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

/**
 * tsmToDateTsm — получить tsm начала дня (обнулить время)
 * 
 * @param {number} tsm - timestamp in minutes
 * @returns {number} tsm начала дня
 * 
 * Пример:
 * tsmToDateTsm(28401750)  // 28401120 (начало дня 2024-01-01)
 */
function tsmToDateTsm(tsm) {
    if (tsm === null || tsm === undefined) return null;
    return Math.floor(tsm / MINUTES_IN_DAY) * MINUTES_IN_DAY;
}

/**
 * dayOfWeek — получить день недели (1-7, где 1=понедельник)
 * 
 * @param {number} tsm - timestamp in minutes
 * @returns {number} день недели (1-7)
 */
function dayOfWeek(tsm) {
    if (tsm === null || tsm === undefined) return null;
    
    // Создаём дату из UTC минут
    const date = new Date(tsm * MS_IN_MINUTE);
    
    // getUTCDay(): 0=воскресенье, 1=понедельник, ...
    // Конвертируем: 0→7, 1→1, 2→2, ..., 6→6
    const dow = date.getUTCDay();
    return dow === 0 ? 7 : dow;
}

/**
 * inBounds — проверить, попадает ли tsm в границы
 * 
 * @param {number} tsm - timestamp in minutes
 * @param {Object} bounds - { start_tsm, end_tsm }
 * @returns {boolean} true если внутри границ
 */
function inBounds(tsm, bounds) {
    if (!bounds) return false;
    
    // Проверка start
    if (bounds.start_tsm !== null && tsm < bounds.start_tsm) {
        return false;
    }
    
    // Проверка end (не включая саму границу end)
    if (bounds.end_tsm !== null && tsm >= bounds.end_tsm) {
        return false;
    }
    
    return true;
}

/**
 * intervalsContains — найти интервал, содержащий tsm
 * 
 * @param {Array} intervals - массив интервалов [[start, end, meta], ...]
 * @param {number} tsm - timestamp in minutes
 * @returns {Array|null} интервал или null
 */
function intervalsContains(intervals, tsm) {
    if (!intervals || intervals.length === 0) return null;
    
    // Конвертируем tsm в минуты от начала дня
    const dayStart = tsmToDateTsm(tsm);
    const minutesFromDay = tsm - dayStart;
    
    for (const interval of intervals) {
        const start = interval[0];
        const end = interval[1];
        
        if (minutesFromDay >= start && minutesFromDay < end) {
            return interval;
        }
    }
    
    return null;
}

/**
 * intervalsSubtract — вычесть интервал из массива интервалов
 * 
 * @param {Array} intervals - массив интервалов [[start, end, meta], ...]
 * @param {Array} subtract - интервал для вычитания [start, end, meta]
 * @returns {Array} результирующие интервалы
 */
function intervalsSubtract(intervals, subtract) {
    if (!intervals || intervals.length === 0) return [];
    if (!subtract || subtract[1] <= subtract[0]) return [...intervals];
    
    const subStart = subtract[0];
    const subEnd = subtract[1];
    
    const result = [];
    
    for (const interval of intervals) {
        const intStart = interval[0];
        const intEnd = interval[1];
        
        // Нет пересечения
        if (subEnd <= intStart || subStart >= intEnd) {
            result.push(interval);
            continue;
        }
        
        // Полностью покрывает — удаляем
        if (subStart <= intStart && subEnd >= intEnd) {
            continue;
        }
        
        // Пересекается слева
        if (subStart > intStart && subEnd >= intEnd) {
            result.push([intStart, subStart, interval[2]]);
            continue;
        }
        
        // Пересекается справа
        if (subStart <= intStart && subEnd < intEnd) {
            result.push([subEnd, intEnd, interval[2]]);
            continue;
        }
        
        // Разделяет на две части
        if (subStart > intStart && subEnd < intEnd) {
            result.push([intStart, subStart, interval[2]]);
            result.push([subEnd, intEnd, interval[2]]);
        }
    }
    
    return result;
}

/**
 * intervalsAdd — добавить интервал с приоритетом его meta
 * 
 * При наложении интервала override на массив интервалов:
 * - Пересекающиеся части получают meta от override
 * - Части вне override сохраняют оригинальную meta
 * - Если override полностью перекрывает интервал — заменяется
 * - Если override не пересекается с интервалами — добавляется как новый интервал
 * 
 * @param {Array} intervals - массив интервалов [[start, end, meta], ...]
 * @param {Array} override - интервал для добавления [start, end, meta]
 * @returns {Array} результирующие интервалы
 */
function intervalsAdd(intervals, override) {
    if (!intervals || intervals.length === 0) {
        return override ? [[...override]] : [];
    }
    
    if (!override || override[1] <= override[0]) {
        return [...intervals];
    }
    
    // 1. Вычитаем override из интервалов (освобождаем место)
    let result = intervalsSubtract(intervals, override);
    
    // 2. Добавляем override в массив
    result.push([...override]);
    
    return result;
}

// =============================================================================
// ЭКСПОРТ (для использования как модуль)
// =============================================================================

// Для Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ScheduleRuntime,
        strToTsm,
        tsmToStr,
        tsmToDateTsm,
        dayOfWeek,
        inBounds,
        intervalsContains,
        intervalsSubtract,
        intervalsAdd
    };
}

// Для браузера
if (typeof window !== 'undefined') {
    window.ScheduleRuntime = ScheduleRuntime;
    window.strToTsm = strToTsm;
    window.tsmToStr = tsmToStr;
    window.tsmToDateTsm = tsmToDateTsm;
    window.dayOfWeek = dayOfWeek;
    window.inBounds = inBounds;
    window.intervalsContains = intervalsContains;
    window.intervalsSubtract = intervalsSubtract;
    window.intervalsAdd = intervalsAdd;
}