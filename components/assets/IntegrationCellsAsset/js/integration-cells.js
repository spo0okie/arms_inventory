/**
 * Батч-наполнение ячеек интеграций в гридах и бейджей у элементов
 * (docs/dev/integrations.md §5 «Колонки в списках», «Бейджи у элементов»).
 *
 * Колонка (CellColumn) рендерит протухшие ячейки с классом
 * .integration-cell-stale и data-атрибутами (provider/column/class/id/url).
 * Скрипт собирает их по (url, провайдер, колонка, класс) и наполняет
 * ОДНИМ POST /integrations/cells на группу — не по запросу на строку.
 * CSRF-заголовок вешает yii.js (зависимость ассета).
 */
(function ($) {
	'use strict';

	function loadIntegrationCells(root) {
		var groups = {};
		$('.integration-cell-stale', root || document).each(function () {
			var $cell = $(this);
			if ($cell.data('cellsLoading')) return; //уже в полёте
			$cell.data('cellsLoading', 1);
			var key = [$cell.data('url'), $cell.data('provider'),
				$cell.data('column'), $cell.data('class')].join('|');
			if (!groups[key]) groups[key] = {cells: [], ids: {}};
			groups[key].cells.push($cell);
			groups[key].ids[$cell.data('id')] = 1;
		});

		$.each(groups, function (key, group) {
			var parts = key.split('|');
			$.post(parts[0], {
				provider: parts[1],
				column: parts[2],
				'class': parts[3],
				ids: Object.keys(group.ids)
			}, function (data) {
				$.each(group.cells, function (i, $cell) {
					var html = data[$cell.data('id')];
					if (typeof html === 'undefined') return;
					$cell.html(html).css('opacity', '')
						.removeClass('integration-cell-stale')
						.removeData('cellsLoading');
				});
			}).fail(function () {
				//сеть/сервер недоступны: снимаем метку «в полёте», чтобы
				//следующий проход (pjax) мог повторить
				$.each(group.cells, function (i, $cell) {
					$cell.removeData('cellsLoading');
				});
			});
		});
	}

	/**
	 * Тикающая длительность: элемент с data-integration-elapsed="<unix ts
	 * начала>" показывает, сколько прошло (время VPN-сессии). В HTML ячейки
	 * лежит момент начала, а не длительность: кэш ячеек общий и живёт
	 * ttl — готовое «5м» через минуту врало бы. Формат — как у провайдеров
	 * на сервере: 3д 4ч / 2ч 05м / 7м 09с.
	 */
	function formatElapsed(seconds) {
		seconds = Math.max(0, Math.floor(seconds));
		var d = Math.floor(seconds / 86400),
			h = Math.floor(seconds % 86400 / 3600),
			m = Math.floor(seconds % 3600 / 60),
			s = seconds % 60,
			pad = function (n) { return (n < 10 ? '0' : '') + n; };
		if (d) return d + 'д ' + h + 'ч';
		if (h) return h + 'ч ' + pad(m) + 'м';
		return m + 'м ' + pad(s) + 'с';
	}

	function tickElapsed() {
		var now = Date.now() / 1000;
		$('[data-integration-elapsed]').each(function () {
			var since = parseInt(this.getAttribute('data-integration-elapsed'), 10);
			if (!isNaN(since)) $(this).text(formatElapsed(now - since));
		});
	}

	$(function () {
		loadIntegrationCells();
		tickElapsed();
		setInterval(tickElapsed, 1000);
	});
	//грид перерисован pjax'ом (фильтрация, issue #146) — доехать новые ячейки
	$(document).on('pjax:end', function (e) { loadIntegrationCells(e.target); });

	//бейджи у элементов (ItemBadgesWidget) приезжают и в ajax-контенте —
	//асинхронные вкладки, подгружаемые блоки карточек: ловим появление
	//новых протухших ячеек, пачкой (debounce) — чтобы блок из сотни IP
	//дал один запрос, а не сотню
	if (window.MutationObserver) {
		var pending = null;
		new MutationObserver(function (mutations) {
			//реагируем только на добавленные протухшие ячейки: тикающие
			//таймеры и наполнение самих ячеек тоже меняют DOM
			for (var i = 0; i < mutations.length; i++) {
				var nodes = mutations[i].addedNodes;
				for (var j = 0; j < nodes.length; j++) {
					var node = nodes[j];
					if (node.nodeType !== 1) continue;
					if ($(node).is('.integration-cell-stale') || node.querySelector('.integration-cell-stale')) {
						clearTimeout(pending);
						pending = setTimeout(function () { loadIntegrationCells(); }, 50);
						return;
					}
				}
			}
		}).observe(document.documentElement, {childList: true, subtree: true});
	}
})(jQuery);
