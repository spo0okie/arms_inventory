<?php
/**
 * Таблица сетевых портов устройства - общая для карточки и для результата
 * опроса коммутатора (plans/network-map.md, этап 3.4).
 *
 * Опрос не приносит новую сущность: он подтверждает или опровергает то, что
 * записано. Патч-корд в iLO сервера и MAC этого iLO на том же порту - один и
 * тот же физический факт с двух сторон, поэтому таблица одна, а результат
 * опроса ложится на неё слоем. Без опроса ($ports = null) выводится ровно то
 * же, что и раньше.
 *
 * Правило показа диффа: совпадение помечается серой галочкой (видно, что порт
 * проверяли), расхождение - действием. Подсвечивать всё подряд нельзя: на 48
 * портах ёлка перестаёт читаться.
 */

use app\components\integrations\providers\MacSearchProvider;
use app\components\PortsRowRenderer;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model \app\models\Techs устройство, чьи порты выводим */
/* @var $ports array|null результат опроса {@see MacSearchProvider::switchPorts()} */
/* @var $passport array|null паспорт портов с коммутатора (её собственный порядок) */
/* @var $transitFrom int с какого числа адресов порт считается транзитным */
/* @var $scanStamp string|null отметка опроса «сформировано за N c, когда» из самих данных */
/* @var $this yii\web\View */

if (!isset($ports)) $ports = null;
if (!isset($passport)) $passport = [];
if (!isset($transitFrom)) $transitFrom = 4;
if (!isset($scanStamp)) $scanStamp = null;
if (!isset($foreignNames)) $foreignNames = [];

//без опроса рисуем объявленные порты как раньше
$rows = $ports;
if (is_null($rows)) {
	$rows = [];
	foreach ($model->portsList as $port) {
		$rows[] = [
			'port' => (string)$port['port_name'],
			'comment' => (string)$port['port_comment'],
			'link' => $port['port_link'],
			'linked' => null, 'found' => [], 'macs' => [], 'vlans' => [],
			'count' => 0, 'transit' => false, 'uplink' => false, 'uplink_peer' => '',
			'verdict' => 'unknown', 'declared' => true, 'neighbors' => [],
			'description' => '', 'admin' => '', 'oper' => '', 'speed' => 0,
			'aggregate' => '', 'vlans_configured' => false,
		];
	}
}

//содержимое строки о порте собирает общий рендерер: таблица и раскладка
//корпуса показывают одни и те же факты, отличаются только тем, куда их кладут
$renderer = new PortsRowRenderer($model, $rows, !is_null($ports), $transitFrom);

//имена и ПОРЯДОК берём из паспорта: он идёт по ifIndex, то есть по железу, а
//строки таблицы уже разложены по объявленному порядку инвентаризации
$scannedNames = [];
foreach ($passport as $item) {
	$name = (string)($item['name'] ?? '');
	if (!strlen($name)) continue;
	//порт соседа по стеку - не наш: у него своя карточка и своё объявление
	if (in_array($name, $foreignNames, true)) continue;
	//на корпусе существуют только розетки: ifType 6 (ethernetCsmacd). Агрегат,
	//Vlan-интерфейс и loopback портами объявлять нельзя
	if (isset($item['type'])
		? (int)$item['type'] !== 6
		: MacSearchProvider::isAggregate($name)) continue;
	$scannedNames[] = $name;
}
//«взять имена» - главный инструмент починки рассинхрона объявления с
//железкой (сопоставление имён строгое, кривое объявление видно целиком),
//поэтому кнопка живёт от ПАСПОРТА, а не от того, что удалось сопоставить.
//Нельзя брать лишь когда паспорт короче объявленного корпуса (список стал
//бы меньше числа розеток) или когда брать нечего/незачем
$declaredNames = [];
foreach ($rows as $port) {
	if (($port['physical'] ?? true) && !empty($port['declared'])) $declaredNames[] = (string)$port['port'];
}
$adoptable = !is_null($ports) && count($scannedNames) > 0
	&& count($scannedNames) >= count($declaredNames);
//записи переезжают по позициям - предупреждение показывает, что во что
$renames = [];
foreach ($declaredNames as $index => $declared) {
	if (($scannedNames[$index] ?? '') !== '' && $scannedNames[$index] !== $declared) {
		$renames[] = $declared.' → '.$scannedNames[$index];
	}
}
//переименовывать нечего - кнопка не нужна; но у устройства без объявленных
//портов вообще взять имена с коммутатора - единственный способ их объявить
if (!count($renames) && count($model->portsTemplate) && count($declaredNames)) $adoptable = false;

//после действия перезапрашиваем панель: вердикты пересчитываются на свежих
//данных инвентаризации, а сам опрос берётся из кэша сервиса
$containerId = 'techs-ports-'.$model->id;
$reload = Url::to(['/integrations/panel', 'provider' => 'macsearch', 'panel' => 'switch',
	'class' => 'techs', 'id' => $model->id, 'refresh' => 1]);


?>
<?php /* раскладка портов: та же таблица, но каждая строка стоит колонкой над
       своей розеткой. Рисуется рядом с таблицей и прячется стилем - режим
       переключает кнопка снаружи контейнера, поэтому опрос (он перерисовывает
       контейнер целиком) выбранного вида не сбивает */ ?>
<?= \app\components\PortsLayoutWidget::widget([
	'model' => $model,
	'rows' => $rows,
	'renderer' => $renderer,
]) ?>

<?php
//колонка агрегации нужна, только если группы вообще есть: у большинства
//железок её содержимое пусто, а место она занимает
$hasAggregates = $renderer->hasAggregates();
//сколько расхождений и сколько из них можно принять одной кнопкой
$offered = $renderer->offered();
$acceptable = $renderer->acceptable();
?>
<table class="table table-striped table-sm ports-classic" id="<?= $containerId ?>-table">
	<tr>
		<th>Порт</th>
		<?php if ($hasAggregates) { ?><th>Агрегация</th><?php } ?>
		<th>Пояснение</th>
		<th colspan="3">Соединение с</th>
		<th></th>
	</tr>

<?php foreach ($rows as $port) {
	$parts = $renderer->parts($port);
	$connection = $parts['connection'];
	?>
	<tr>
		<td><?= $parts['port'] ?></td>
		<?php if ($hasAggregates) { ?>
			<td class="text-nowrap"><?= $parts['aggregate'] ?></td>
		<?php } ?>
		<td><?= $parts['comment'] ?></td>

		<?php if ($connection['mode'] === 'none') { ?>
			<td colspan="3"><?= $connection['body'] ?></td>
		<?php } else { ?>
			<td><span class="fas fa-exchange-alt"></span></td>
			<td><?= $connection['comment'] ?></td>
			<td><?= $connection['body'] ?></td>
		<?php } ?>

		<td class="text-nowrap"><?= $parts['action'] ?></td>
	</tr>

<?php } ?>
</table>

<?php if (!is_null($ports)) { ?>
	<?php /* итог опроса: сколько расхождений и когда снимали данные. Отметка
	       времени - из самих данных опроса, а не время рендера: так видно и
	       «устройство выключили пять минут назад», и «это тот же ответ, что
	       40 секунд назад» - откуда бы он ни пришёл (кэш панели, кэш сервиса,
	       присоединение к идущему опросу) */ ?>
	<div class="text-secondary small mb-2">
		<?php if ($offered) { ?>
			расхождений: <?= $offered ?>.
		<?php } else { ?>
			расхождений нет.
		<?php } ?>
		<?= Html::encode((string)$scanStamp) ?>

		<?php if ($adoptable) { ?>
			<?php /* коммутатор знает свои порты лучше, чем модельный шаблон: за
			       именем стоит кабель, и записи переезжают за своими позициями */ ?>
			<?= Html::button('Взять имена портов с коммутатора', [
				'class' => 'btn btn-sm btn-outline-secondary ms-2',
				'qtip_ttip' => 'Объявить порты этого устройства так, как оно называет их само',
				'data-scan' => json_encode([
					'tech' => $model->id,
					'do' => 'names',
					'names' => implode("\n", $scannedNames),
				]),
				'data-confirm' => 'Порты будут объявлены так, как их называет коммутатор ('
					.count($scannedNames).').'."\n"
					.'Заведённые записи переименуются по позициям: '
					.implode(', ', array_slice($renames, 0, 3))
					.(count($renames) > 3 ? ' и ещё '.(count($renames) - 3) : '')."\n"
					.'Связи портов при этом сохраняются.',
				'onclick' => 'portsScanApply(this)',
			]) ?>
		<?php } ?>

		<?php if ($acceptable > 1) { ?>
			<?php /* "убрать" разом не даём: снятие связи опаснее привязки, и
			       каждое подтверждается отдельно */ ?>
			<?= Html::button('Добавить однозначные совпадения ('.$acceptable.')', [
				'class' => 'btn btn-sm btn-outline-success ms-2',
				'qtip_ttip' => 'Привязать всё обнаруженное. Снятие связей разом не '
					.'выполняется: каждое подтверждается отдельно',
				'onclick' => 'portsScanAcceptAll(this)',
			]) ?>
		<?php } ?>
	</div>

	<script>
		//применение находки: POST в инвентаризацию и перерисовка таблицы.
		//Скрипт приезжает вместе с таблицей и переопределяется при каждой её
		//перерисовке - так проще, чем следить за живучестью обработчиков
		window.portsScanApply = function (button, quiet, done) {
			var element = $(button), data = element.data('scan'),
				confirmation = element.data('confirm');
			if (!quiet && confirmation && !confirm(confirmation)) return;

			//селекты "порт на той стороне" живут в строке предложения, в другой
			//ячейке той же строки таблицы: ищем по номеру предложения. В
			//раскладке корпуса строка - одна повёрнутая ячейка, границу задаёт она
			element.closest('tr, .ports-layout-cell')
				.find('.port-scan-peer[data-proposal="' + element.data('proposal') + '"]')
				.each(function () {
					var field = $(this).data('field');
					data[field] = $(this).val();
					data[field + '_name'] = $(this).find(':selected').data('name');
					//переключатель моста: второй порт уходит к устройству за мостом
					if ($(this).data('toggle-via')) {
						var other = $(this).find('option:not(:selected)').first();
						data.via = other.val();
						data.via_name = other.data('name');
					}
				});

			element.prop('disabled', true);
			$.post(<?= json_encode(Url::to(['/ports/scan-apply'])) ?>, data, function (answer) {
				if (answer.status !== 'ok') {
					alert(answer.error || 'не получилось');
					element.prop('disabled', false);
				}
				if (done) done();
				else portsScanReload();
			}, 'json');
		};

		//разом - только привязка: снятие связи опаснее и подтверждается поштучно.
		//Берём кнопки ТАБЛИЦЫ: раскладка корпуса показывает те же находки, и по
		//обоим видам сразу каждая применилась бы дважды
		window.portsScanAcceptAll = function () {
			var buttons = $('#<?= $containerId ?> .ports-classic').find('.port-scan-accept')
				.map(function () { return $(this).is('button') ? this : $(this).find('button')[0]; })
				.toArray();
			if (!buttons.length || !confirm('Принять находки: ' + buttons.length + '?')) return;

			var next = function () {
				var current = buttons.shift();
				if (!current) return portsScanReload();
				portsScanApply(current, true, next);
			};
			next();
		};

		//переключили порт моста - подпись второго звена меняется сразу
		$('#<?= $containerId ?>').on('change', '.port-scan-peer[data-toggle-via]', function () {
			$(this).closest('tr, .ports-layout-cell')
				.find('.port-scan-via[data-proposal="' + $(this).data('proposal') + '"]')
				.text($(this).find('option:not(:selected)').first().text());
		});

		window.portsScanReload = function () {
			$('#<?= $containerId ?>').load(<?= json_encode($reload) ?>);
		};
	</script>
<?php } ?>
