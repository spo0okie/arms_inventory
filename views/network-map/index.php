<?php
/**
 * Карта сети площадки (docs/dev/network-map.md): схема коммутаторов из
 * записей инвентаризации и тот же граф списком — рёбра с портами обеих
 * сторон. Схема отвечает «как соединено», список — «через какие порты» и
 * даёт ссылки на карточки портов; на 70 узлах подписи рёбер на схеме
 * читаются плохо, список нужен именно поэтому.
 *
 * «Сверить с сетью» опрашивает соседей (LLDP/CDP) всех коммутаторов
 * площадки и перерисовывает карту со слоем: подтверждено / не видно /
 * найдено, но не записано (с кнопкой записать) / неопознанные соседи.
 */

use app\components\assets\MermaidAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $sites \app\models\Places[] площадки на выбор */
/* @var $site \app\models\Places|null выбранная площадка */
/* @var $map \app\components\NetworkMap|null */
/* @var $provider \app\components\integrations\providers\MacSearchProvider|null */
/* @var $rooms bool группировать узлы по помещениям */

$this->title = 'Карта сети'.(is_object($site) ? ': '.$site->name : '');
$this->params['breadcrumbs'][] = 'Карта сети';

MermaidAsset::register($this);
//mermaid в документации инициализируется в strict (клики отключены); карте
//клики по узлам нужны - своя инициализация, текст диаграммы наш
//(имена экранированы в NetworkMap::quote()), чужого HTML в нём нет
$this->registerJs(<<<'JS'
window.networkMapRender = function () {
	if (typeof mermaid === 'undefined') return;
	var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
	mermaid.initialize({ startOnLoad: false, theme: dark ? 'dark' : 'neutral', securityLevel: 'loose',
		flowchart: { useMaxWidth: true, htmlLabels: true } });
	var el = document.getElementById('network-map-diagram');
	if (el && !el.getAttribute('data-processed')) mermaid.run({ nodes: [el] });
};
networkMapRender();
JS
, \yii\web\View::POS_END, 'network-map-init');

$scanUrl = is_object($site)
	? Url::to(['/network-map/scan', 'place' => $site->id, 'rooms' => $rooms ? 1 : 0]) : null;
?>
<h1><?= Html::encode($this->title) ?></h1>

<form method="get" class="row g-2 align-items-center mb-3">
	<div class="col-auto">
		<?= Html::dropDownList('place', is_object($site) ? $site->id : null,
			\yii\helpers\ArrayHelper::map($sites, 'id', 'name'),
			['class' => 'form-select', 'onchange' => 'this.form.submit()',
				'prompt' => 'Площадка…']) ?>
	</div>
	<?php if (is_object($map)) { ?>
		<div class="col-auto form-check">
			<?php /* рамки помещений: на большой площадке видно, что стоит в
			       какой серверной, и куда уходят межкомнатные линки */ ?>
			<?= Html::checkbox('rooms', $rooms, ['class' => 'form-check-input', 'value' => 1,
				'id' => 'network-map-rooms', 'onchange' => 'this.form.submit()']) ?>
			<label class="form-check-label" for="network-map-rooms">учитывать помещения</label>
		</div>
		<div class="col-auto text-secondary small">
			коммутаторов: <?= count($map->nodes) ?>, связей: <?= count($map->edges) ?><?php
			if (count($map->outside)) { ?>, на другие площадки: <?= count($map->outside) ?><?php } ?>
		</div>
		<?php if ($provider && count($map->nodes)) { ?>
			<div class="col-auto">
				<?= Html::button('Сверить с сетью', ['class' => 'btn btn-sm btn-secondary',
					'id' => 'network-map-scan', 'onclick' => 'networkMapScan(0)',
					'qtip_ttip' => 'Опросить соседей по LLDP/CDP у всех коммутаторов площадки и '
						.'наложить на карту: что подтверждено, чего не видно, что найдено и не записано']) ?>
			</div>
		<?php } ?>
	<?php } ?>
</form>

<?php if (!is_object($map)) { ?>
	<div class="alert alert-secondary">Нет площадок с коммутаторами.</div>
<?php } elseif (!count($map->nodes)) { ?>
	<div class="alert alert-secondary">
		На площадке <?= Html::encode($site->name) ?> нет оборудования с типом модели
		«коммутатор» (<?= Html::encode(implode(', ', \app\components\NetworkMap::switchTypes())) ?>).
	</div>
<?php } else { ?>
	<div id="network-map-body">
		<?= $this->render('_map', ['map' => $map, 'site' => $site, 'provider' => $provider, 'scanStamp' => null]) ?>
	</div>

	<script>
		//сверка: pending - ждём и перезапрашиваем (сервис присоединяет к идущему опросу)
		window.networkMapScan = function (attempt, reuse) {
			var button = $('#network-map-scan'), label = button.data('label') || button.html();
			button.data('label', label).prop('disabled', true)
				.html('<span class="spinner-border spinner-border-sm"></span> опрос…');
			//диагностика - на странице, а не в alert: окно закрыл - и разбираться не по чему
			var trouble = function (text) {
				$('#network-map-trouble').remove();
				$('<div id="network-map-trouble" class="alert alert-warning py-2 my-2"></div>')
					.text(text).prependTo('#network-map-body');
			};
			$.ajax({url: <?= json_encode($scanUrl) ?> + '&attempt=' + attempt + (reuse ? '&reuse=1' : ''),
				timeout: 120000})
				.done(function (data, status, xhr) {
					var json = typeof data === 'object' ? data : null;
					if (json && json.status === 'pending') {
						if (json.more) return setTimeout(function () { networkMapScan(json.attempt); }, 15000);
						trouble('Опрос не уложился в отведённое число попыток (' + json.attempt + '). '
							+ 'Сервис должен укладываться в свой scan.deadline - кто тормозит, '
							+ 'видно в его журнале по строкам «полный опрос занял».');
					} else if (json && json.status === 'error') {
						trouble('Сверка не выполнена: ' + json.error);
					} else {
						$('#network-map-body').html(data);
						networkMapRender();
					}
					button.prop('disabled', false).html(label);
				})
				.fail(function (xhr, status) {
					trouble('Сверка не выполнена: ' + (xhr.status ? 'сервер ответил ' + xhr.status : 'нет ответа'));
					button.prop('disabled', false).html(label);
				});
		};

		//запись одной найденной связи: тот же endpoint, что у таблицы портов
		window.mapScanApply = function (button, quiet, done) {
			var element = $(button), data = element.data('scan');
			element.closest('tr').find('.map-scan-peer[data-finding="' + element.data('finding') + '"]')
				.each(function () {
					data[$(this).data('field')] = $(this).val();
					data[$(this).data('field') + '_name'] = $(this).find(':selected').data('name');
				});
			element.prop('disabled', true);
			$.post(<?= json_encode(Url::to(['/ports/scan-apply'])) ?>, data, function (answer) {
				if (answer.status !== 'ok') {
					alert(answer.error || 'не получилось');
					element.prop('disabled', false);
				}
				//карта пересобирается с последним опросом, площадку заново не дёргаем
				if (done) done(); else networkMapScan(0, 1);
			}, 'json');
		};

		//сопоставление неопознанного соседа с карточкой: дописать имя/адрес и
		//пересверить (опрос из кэша, площадка заново не дёргается)
		window.mapAssign = function (button) {
			var element = $(button), data = element.data('assign');
			var select = element.closest('td').find('.map-assign-tech');
			data.tech = select.val();
			if (!data.tech) { alert('Выберите, какой это коммутатор'); return; }
			element.prop('disabled', true);
			$.post(<?= json_encode(Url::to(['/network-map/assign'])) ?>, data, function (answer) {
				if (answer.status !== 'ok') {
					alert(answer.error || 'не получилось');
					element.prop('disabled', false);
					return;
				}
				if (answer.warning) alert(answer.warning);
				networkMapScan(0, 1);
			}, 'json');
		};

		//всё однозначное разом; спорное (селект, замена записанного) - поштучно
		window.mapScanAcceptAll = function () {
			var buttons = $('#network-map-body').find('button.map-scan-accept').toArray();
			if (!buttons.length || !confirm('Записать связей: ' + buttons.length + '?')) return;
			var next = function () {
				var current = buttons.shift();
				if (!current) return networkMapScan(0, 1);
				mapScanApply(current, true, next);
			};
			next();
		};
	</script>
<?php } ?>
