/**
 * Батч-наполнение ячеек интеграций в гридах (docs/dev/integrations.md §5 «Колонки в списках»).
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

	$(function () { loadIntegrationCells(); });
	//грид перерисован pjax'ом (фильтрация, issue #146) — доехать новые ячейки
	$(document).on('pjax:end', function (e) { loadIntegrationCells(e.target); });
})(jQuery);
