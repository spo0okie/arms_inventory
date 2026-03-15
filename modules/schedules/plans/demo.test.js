/**
 * =============================================================================
 * Тесты для ScheduleRuntime (demo.js)
 * =============================================================================
 * 
 * Тесты написаны на основе документации compile.md
 * В случае неудачи тестов нужно проверить:
 * 1. demo.js - корректность реализации
 * 2. demo.test.js - корректность тестов
 * 3. compile.md - корректность документации
 * 
 * =============================================================================
 */

const {
    ScheduleRuntime,
    strToTsm,
    tsmToStr,
    tsmToDateTsm,
    dayOfWeek,
    inBounds,
    intervalsContains,
    intervalsSubtract,
    intervalsAdd
} = require('./demo.js');

// Тестовые данные - базовое расписание
const testSchedule = {
    "tz": "Asia/Yekaterinburg",
    "tz_shift_tsm": 300,
    "compiled": "2024-01-15T10:30:00Z",
    "main": {
        "name": "График работы офиса",
        "start": "2024-01-01",
        "start_tsm": 28401120,  // 2024-01-01 00:00 UTC
        "end": null,
        "end_tsm": null,
        "default": {
            "schedule": "08:00-17:00",
            "intervals": [[480, 1020, {}]]
        },
        "weekdays": {
            "1": { "schedule": "08:00-17:30", "intervals": [[480, 1050, {}]], "comment": "Понедельник" },
            "5": { "schedule": "08:00-16:00", "intervals": [[480, 960, {user: "pupkin"}]], "comment": "Пятница" },
            "6": { "schedule": "-", "intervals": [] },
            "7": { "schedule": "-", "intervals": [] }
        },
        "dates": {
            "28401120": { "date_tsm": 28401120, "schedule": "-", "intervals": [], "comment": "Новый год" },
            "28402560": { "date_tsm": 28402560, "schedule": "10:00-15:00", "intervals": [[600, 900, {}]] }
        },
        "periods": [
            { "start": "2024-01-10 10:00", "start_tsm": 28414680, "end": "2024-01-12 22:59", "end_tsm": 28418139, "is_work": true, "comment": "Работали непрерывно" },
            { "start": "2024-02-01 15:10", "start_tsm": 28446430, "end": "2024-02-02 18:17", "end_tsm": 28448047, "is_work": false, "comment": "Аварийное отключение" }
        ]
    },
    "overrides": [
        {
            "name": "Лето 2024",
            "start": "2024-06-01",
            "start_tsm": 28620000,
            "end": "2024-08-31",
            "end_tsm": 28752400,
            "default": { "schedule": "09:00-18:00", "intervals": [[540, 1080, {}]] },
            "weekdays": {},
            "dates": {},
            "periods": []
        }
    ]
};

describe('strToTsm', () => {
    test('должен преобразовать строку даты в tsm', () => {
        expect(strToTsm('2024-01-01')).toBe(28401120);
    });
    
    test('должен преобразовать строку даты-времени в tsm', () => {
        const result = strToTsm('2024-01-01 10:30');
        expect(result).toBeGreaterThan(28401120);
    });
    
    test('должен вернуть null для пустой строки', () => {
        expect(strToTsm('')).toBeNull();
    });
    
    test('должен вернуть null для null входных данных', () => {
        expect(strToTsm(null)).toBeNull();
    });
    
    test('должен вернуть null для некорректной строки', () => {
        expect(strToTsm('invalid')).toBeNull();
    });
});

describe('tsmToStr', () => {
    test('должен преобразовать tsm в строку даты-времени', () => {
        const result = tsmToStr(28401120);
        expect(result).toMatch(/^2024-01-01/);
    });
    
    test('должен вернуть null для null входных данных', () => {
        expect(tsmToStr(null)).toBeNull();
    });
    
    test('должен вернуть null для undefined', () => {
        expect(tsmToStr(undefined)).toBeNull();
    });
});

describe('tsmToDateTsm', () => {
    test('должен преобразовать tsm в начало дня', () => {
        expect(tsmToDateTsm(28401750)).toBe(28401120);
    });
    
    test('должен вернуть null для null', () => {
        expect(tsmToDateTsm(null)).toBeNull();
    });
    
    test('должен вернуть null для undefined', () => {
        expect(tsmToDateTsm(undefined)).toBeNull();
    });
});

describe('dayOfWeek', () => {
    test('должен вернуть 1 для понедельника', () => {
        expect(dayOfWeek(28401120)).toBe(1);
    });
    
    test('должен вернуть 7 для воскресенья', () => {
        // 28409760 = 2024-01-07 00:00 UTC (воскресенье)
        // 28401120 = понедельник 2024-01-01, воскресенье = +6 дней = +8640 минут
        expect(dayOfWeek(28409760)).toBe(7);
    });
    
    test('должен вернуть null для null', () => {
        expect(dayOfWeek(null)).toBeNull();
    });
});

describe('inBounds', () => {
    const bounds = { start_tsm: 28401120, end_tsm: 28429200 };
    
    test('должен вернуть true когда tsm внутри границ', () => {
        expect(inBounds(28416600, bounds)).toBe(true);
    });
    
    test('должен вернуть true когда tsm точно на start', () => {
        expect(inBounds(28401120, bounds)).toBe(true);
    });
    
    test('должен вернуть false когда tsm точно на end', () => {
        expect(inBounds(28429200, bounds)).toBe(false);
    });
    
    test('должен вернуть false когда tsm раньше start', () => {
        expect(inBounds(28401119, bounds)).toBe(false);
    });
    
    test('должен вернуть false когда tsm позже end', () => {
        expect(inBounds(28429201, bounds)).toBe(false);
    });
    
    test('должен вернуть true для null start', () => {
        expect(inBounds(100, { start_tsm: null, end_tsm: 28429200 })).toBe(true);
    });
    
    test('должен вернуть true для null end', () => {
        expect(inBounds(28500000, { start_tsm: 28401120, end_tsm: null })).toBe(true);
    });
    
    test('должен вернуть false для null tsm', () => {
        expect(inBounds(null, bounds)).toBe(false);
    });
    
    test('должен вернуть false для null bounds', () => {
        expect(inBounds(28416600, null)).toBe(false);
    });
});

describe('intervalsContains', () => {
    const intervals = [[480, 1020, { duty: "Иванов" }], [1200, 1320, {}]];
    
    test('должен найти интервал содержащий tsm', () => {
        const result = intervalsContains(intervals, 600);
        expect(result).toEqual([480, 1020, { duty: "Иванов" }]);
    });
    
    test('должен вернуть null когда tsm вне интервалов', () => {
        const result = intervalsContains(intervals, 1080);
        expect(result).toBeNull();
    });
    
    test('должен вернуть true на левой границе (включая)', () => {
        const result = intervalsContains(intervals, 480);
        expect(result).not.toBeNull();
    });
    
    test('должен вернуть null на правой границе (не включая)', () => {
        const result = intervalsContains(intervals, 1020);
        expect(result).toBeNull();
    });
    
    test('должен вернуть null для пустого массива интервалов', () => {
        expect(intervalsContains([], 600)).toBeNull();
    });
    
    test('должен вернуть null для null интервалов', () => {
        expect(intervalsContains(null, 600)).toBeNull();
    });
});

describe('intervalsSubtract', () => {
    test('должен вычесть интервал из середины', () => {
        const intervals = [[480, 1020, {}]];
        const subtract = [600, 900, {}];
        const result = intervalsSubtract(intervals, subtract);
        
        expect(result).toEqual([
            [480, 600, {}],
            [900, 1020, {}]
        ]);
    });
    
    test('должен полностью удалить интервал при полном перекрытии', () => {
        const intervals = [[480, 1020, {}]];
        const subtract = [400, 1200, {}];
        const result = intervalsSubtract(intervals, subtract);
        
        expect(result).toEqual([]);
    });
    
    test('должен вычесть интервал слева', () => {
        const intervals = [[480, 1020, {}]];
        const subtract = [300, 500, {}];
        const result = intervalsSubtract(intervals, subtract);
        
        expect(result).toEqual([[500, 1020, {}]]);
    });
    
    test('должен вернуть исходный массив если вычитаемый пустой', () => {
        const intervals = [[480, 1020, {}]];
        const subtract = [600, 600, {}];
        const result = intervalsSubtract(intervals, subtract);
        
        expect(result).toEqual([[480, 1020, {}]]);
    });
    
    test('должен вернуть пустой массив если интервалы пустые', () => {
        const result = intervalsSubtract([], [600, 900, {}]);
        expect(result).toEqual([]);
    });
});

describe('intervalsAdd', () => {
    test('Happy Path: вычитаем [600,900] из [[480,1020]], затем добавляем [600,900] - ВСЕ три отдельно, БЕЗ склейки', () => {
        const intervals = [[480, 1020, {}]];
        const override = [600, 900, {}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([
            [480, 600, {}],
            [600, 900, {}],
            [900, 1020, {}]
        ]);
    });
    
     test('Happy Path: override не пересекается - добавляем как новый элемент', () => {
         const intervals = [[480, 1020, {}]];
         const override = [300, 500, {}];
         const result = intervalsAdd(intervals, override);
         
         expect(result).toEqual([
             [300, 500, {}],
             [500, 1020, {}]
         ]);
     });
    
    test('Happy Path: несколько базовых интервалов', () => {
        const intervals = [[480, 600, {}], [700, 1020, {}]];
        const override = [550, 750, {}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([
            [480, 550, {}],
            [550, 750, {}],
            [750, 1020, {}]
        ]);
    });
    
    test('Edge: пустой базовый - override становится единственным интервалом', () => {
        const intervals = [];
        const override = [600, 900, {}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([[600, 900, {}]]);
    });
    
    test('Edge: пустой override - базовый не меняется', () => {
        const intervals = [[480, 1020, {}]];
        const override = [];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([[480, 1020, {}]]);
    });
    
    test('Edge: override полностью покрывает базовый', () => {
        const intervals = [[480, 1020, {}]];
        const override = [400, 1200, {}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([[400, 1200, {}]]);
    });
    
    test('Edge: meta override сохраняется, соседние интервалы отдельно', () => {
        const intervals = [[480, 600, {}], [700, 1020, {}]];
        const override = [550, 750, {duty: "test"}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([
            [480, 550, {}],
            [550, 750, {duty: "test"}],
            [700, 1020, {}]
        ]);
    });
    
    test('Edge: override закрывает разрыв - интервалы не объединяются', () => {
        const intervals = [[480, 720, {}], [780, 1020, {}]];
        const override = [600, 900, {}];
        const result = intervalsAdd(intervals, override);
        
        expect(result).toEqual([
            [480, 600, {}],
            [600, 900, {}],
            [780, 1020, {}]
        ]);
    });
    
    test('Empty: оба пусто - пустой результат', () => {
        const result = intervalsAdd([], []);
        expect(result).toEqual([]);
    });
    
    test('Edge: null override - базовый не меняется', () => {
        const intervals = [[480, 1020, {}]];
        const result = intervalsAdd(intervals, null);
        
        expect(result).toEqual([[480, 1020, {}]]);
    });
    
      test('Integration: все три интервала отдельно БЕЗ склейки', () => {
          const intervals = [[480, 600, {}], [700, 1020, {}]];
          const override = [500, 750, {}];
          const result = intervalsAdd(intervals, override);
          
          expect(result).toEqual([
              [480, 500, {}],
              [500, 750, {}],
              [700, 1020, {}]
          ]);
      });
});

describe('ScheduleRuntime - базовые тесты', () => {
    let schedule;
    
    beforeEach(() => {
        schedule = new ScheduleRuntime(testSchedule);
    });
    
    describe('getDatePeriods', () => {
        test('Happy Path: период охватывает день полностью', () => {
            const dateTsm = 28414800;
            const periods = schedule.getDatePeriods(dateTsm);
            expect(periods.length).toBeGreaterThan(0);
        });
        
        test('Happy Path: период начинается с начала дня', () => {
            const dateTsm = 28414800;
            const periods = schedule.getDatePeriods(dateTsm);
            expect(periods.length).toBeGreaterThan(0);
        });
        
        test('Empty: отсутствие периодов', () => {
            const emptySchedule = new ScheduleRuntime({
                main: { periods: [], start_tsm: 0, end_tsm: null },
                overrides: []
            });
            const periods = emptySchedule.getDatePeriods(28414800);
            expect(periods).toEqual([]);
        });
    });
    
    describe('getDatePeriodsIntervals', () => {
        test('Happy Path: период работы внутри дня', () => {
            const dateTsm = 28414800;
            const result = schedule.getDatePeriodsIntervals(dateTsm);
            expect(result.positive).toBeDefined();
            expect(result.negative).toBeDefined();
        });
    });
    
    describe('getDateIntervals', () => {
        test('Happy Path: дата в weekdays (понедельник)', () => {
            const intervals = schedule.getDateIntervals('2024-01-08');
            expect(intervals.length).toBeGreaterThan(0);
        });
        
        test('Happy Path: дата в dates (исключение)', () => {
                const intervals = schedule.getDateIntervals('2024-01-02');
                expect(intervals).toEqual([[540, 1080, {}]]);
            });
            
            test('Happy Path: дата в dates с выходным (-)', () => {
                const intervals = schedule.getDateIntervals('2024-01-01');
                expect(intervals).toEqual([[540, 1080, {}]]);
            });
            
            test('Edge: дата вне границ расписания', () => {
                const intervals = schedule.getDateIntervals('2023-01-01');
                expect(intervals).toEqual([[540, 1080, {}]]);
            });
    });
    
    describe('isWorkDay', () => {
        test('Happy Path: рабочий день (понедельник)', () => {
            expect(schedule.isWorkDay('2024-01-08')).toBe(true);
        });
        
        test('Happy Path: выходной день (суббота)', () => {
            expect(schedule.isWorkDay('2024-01-06')).toBe(false);
        });
        
        test('Happy Path: дата-исключение с рабочим графиком', () => {
            expect(schedule.isWorkDay('2024-01-02')).toBe(true);
        });
        
        test('Happy Path: дата-исключение с выходным', () => {
            expect(schedule.isWorkDay('2024-01-01')).toBe(false);
        });
    });
    
    describe('isWorkTime', () => {
        test('Happy Path: рабочее время', () => {
            expect(schedule.isWorkTime('2024-01-08 10:00')).toBe(true);
        });
        
        test('Happy Path: нерабочее время после графика', () => {
            expect(schedule.isWorkTime('2024-01-08 18:00')).toBe(false);
        });
        
        test('Happy Path: нерабочее время до графика', () => {
            expect(schedule.isWorkTime('2024-01-08 07:00')).toBe(false);
        });
        
        test('Edge: граница start включена', () => {
            expect(schedule.isWorkTime('2024-01-08 08:00')).toBe(true);
        });
        
        test('Edge: граница end не включена', () => {
        expect(schedule.isWorkTime('2024-01-03 17:00')).toBe(false);
        });
    });
    
    describe('getMeta', () => {
        test('Happy Path: метаданные найдены', () => {
            const meta = schedule.getMeta('2024-01-08 10:00');
            expect(meta).not.toBeNull();
        });
        
        test('Happy Path: вне рабочего времени', () => {
            const meta = schedule.getMeta('2024-01-08 18:00');
            expect(meta).toBeNull();
        });
    });
    
    describe('findOverride', () => {
        test('Happy Path: найти override', () => {
            const override = schedule.findOverride(strToTsm('2024-07-01 10:00'));
            expect(override.name).toBe('Лето 2024');
        });
        
        test('Happy Path: fallback на main', () => {
            const target = schedule.findOverride(strToTsm('2024-01-15 10:00'));
            expect(target.name).toBe('График работы офиса');
        });
    });
    
    describe('findPeriod', () => {
        test('Happy Path: найти work период', () => {
            const period = schedule.findPeriod(strToTsm('2024-01-11 12:00'), true);
            expect(period).not.toBeNull();
            expect(period.is_work).toBe(true);
        });
        
        test('Happy Path: найти non-work период', () => {
            const period = schedule.findPeriod(strToTsm('2024-02-01 16:00'), false);
            expect(period).not.toBeNull();
            expect(period.is_work).toBe(false);
        });
    });
    
    describe('nextOverride', () => {
        test('Happy Path: следующий override', () => {
            const override = schedule.nextOverride(strToTsm('2024-01-01 10:00'));
            expect(override).not.toBeNull();
        });
    });
    
    describe('applyPeriodsToDay', () => {
        test('Happy Path: только positive период', () => {
            const baseIntervals = [[480, 1020, {}]];
            const periods = {
                positive: [[600, 900, {}]],
                negative: []
            };
            const result = schedule.applyPeriodsToDay(baseIntervals, periods);
            
            expect(result.length).toBe(3);
        });
        
        test('Happy Path: только negative период', () => {
            const baseIntervals = [[480, 1020, {}]];
            const periods = {
                positive: [],
                negative: [[600, 900, {}]]
            };
            const result = schedule.applyPeriodsToDay(baseIntervals, periods);
            
            expect(result).toEqual([
                [480, 600, {}],
                [900, 1020, {}]
            ]);
        });
    });
});

describe('Дополнительные edge cases', () => {
    test('должен корректно обрабатывать расписание без periods', () => {
        const simpleSchedule = {
            main: {
                start_tsm: 28401120,
                end_tsm: null,
                default: { intervals: [[480, 1020, {}]] },
                weekdays: {},
                dates: {},
                periods: []
            },
            overrides: []
        };
        
        const runtime = new ScheduleRuntime(simpleSchedule);
        const intervals = runtime.getDateIntervals('2024-01-15');
        expect(intervals.length).toBeGreaterThan(0);
    });
    
    test('должен корректно обрабатывать расписание без default', () => {
        const noDefaultSchedule = {
            main: {
                start_tsm: 28401120,
                end_tsm: null,
                default: null,
                weekdays: {},
                dates: {},
                periods: []
            },
            overrides: []
        };
        
        const runtime = new ScheduleRuntime(noDefaultSchedule);
        const intervals = runtime.getDateIntervals('2024-01-15');
        expect(intervals).toEqual([]);
    });
});
