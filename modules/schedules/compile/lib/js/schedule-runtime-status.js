/**
 * schedule-runtime-status.js
 *
 * Live-отображение активности расписаний на стороне браузера.
 *
 * Ожидаемая разметка в колонке:
 *   <span class="schedule-runtime-status" data-target="#ssrt-{id}"></span>
 *   <script type="application/json" class="ssrt-data" id="ssrt-{id}">{compiled_json}</script>
 *
 * Скрипт:
 * - парсит compiled_json для каждого маркера,
 * - создаёт ScheduleRuntime,
 * - обновляет текст/класс span раз в минуту (а также сразу при загрузке).
 */
(function () {
    if (typeof window === 'undefined') return;

    function findRoots(doc) {
        return doc.querySelectorAll('.schedule-runtime-status');
    }

    function loadRuntime(span) {
        if (span._scheduleRuntime) return span._scheduleRuntime;
        var target = span.getAttribute('data-target');
        var dataNode = target ? document.querySelector(target) : null;
        if (!dataNode) return null;
        try {
            var compiled = JSON.parse(dataNode.textContent || '{}');
            if (!window.ScheduleRuntime) return null;
            span._scheduleRuntime = new window.ScheduleRuntime(compiled);
            return span._scheduleRuntime;
        } catch (e) {
            return null;
        }
    }

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    /**
     * Возвращает "текущее время" в часовом поясе расписания, но в виде строки,
     * которую ScheduleRuntime трактует как UTC. compiled_json хранит все даты/времена
     * как "локальное время, интерпретированное как UTC" (см. SchedulesCompiler::tzShiftMinutes),
     * поэтому для корректного сравнения сдвигаем реальный UTC now на tz_shift_tsm.
     *
     * @param {number} tzShiftMin - минуты сдвига TZ относительно UTC (например, 180 для UTC+3)
     */
    function nowDateTime(tzShiftMin) {
        var d = new Date();
        var shifted = new Date(d.getTime() + (tzShiftMin || 0) * 60000);
        var str = shifted.getUTCFullYear() + '-' + pad(shifted.getUTCMonth() + 1) + '-' + pad(shifted.getUTCDate())
            + ' ' + pad(shifted.getUTCHours()) + ':' + pad(shifted.getUTCMinutes());
        return str;
    }

    function renderOne(span) {
        var rt = loadRuntime(span);
        if (!rt) {
            span.textContent = '—';
            span.className = 'schedule-runtime-status text-muted';
            return;
        }
        var tzShift = (rt.schedule && typeof rt.schedule.tz_shift_tsm === 'number') ? rt.schedule.tz_shift_tsm : 0;
        var active = false;
        try { active = rt.isWorkTime(nowDateTime(tzShift)); } catch (e) { active = false; }
        span.textContent = active ? '●' : '○';
        span.className = 'schedule-runtime-status ' + (active ? 'text-success' : 'text-muted');
        span.title = active ? 'Активно сейчас' : 'Сейчас не активно';
    }

    function renderAll() {
        var roots = findRoots(document);
        for (var i = 0; i < roots.length; i++) renderOne(roots[i]);
    }

    function start() {
        renderAll();
        // Выравниваем обновление по началу следующей минуты, далее — раз в минуту.
        var msToNextMinute = 60000 - (Date.now() % 60000);
        setTimeout(function tick() {
            renderAll();
            setInterval(renderAll, 60000);
        }, msToNextMinute);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
