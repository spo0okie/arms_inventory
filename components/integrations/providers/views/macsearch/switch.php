<?php
/**
 * Панель «Что подключено к портам» в карточке коммутатора: таблица MAC,
 * снятая с этой железки (mode=table сервиса arms.macsearch), разложенная по
 * портам и сопоставленная с объектами инвентаризации.
 *
 * Что за портом, видно по числу адресов: один — устройство, два (обычно в
 * разных VLAN) — телефон с ПК за ним, много — за портом сеть. Транзитные
 * порты (связанные в `ports` с другим коммутатором) помечаются по знанию
 * инвентаризации, а не по числу адресов: оно эту оценку перебивает.
 */

use app\components\widgets\page\ModelWidget;
use app\models\Techs;
use yii\helpers\Html;

/* @var $ports array порты {@see MacSearchProvider::switchPorts()} */
/* @var $data array|null ответ сервиса (status/targets/errors) */
/* @var $error string|null сервис не ответил */
/* @var $refreshUrl string|null URL самоперезапроса панели, пока идёт опрос */
/* @var $tech Techs коммутатор, чью карточку открыли */
/* @var $provider \app\components\integrations\providers\MacSearchProvider */
/* @var $compact bool панель рисуется во вложенном списке - нужно плотнее */

$containerId = 'macsearch-switch-'.$tech->id;
$status = $data['status'] ?? null;

//неопрошенная железка тут ровно одна - это она сама
$failure = ($data['errors'] ?? [])[0] ?? null;

//адреса за портом ссылками на объекты; чей адрес не опознан - показываем как есть
$macCell = static function (array $item) {
	if (!count($item['objects'])) {
		return '<span class="mac_address" title="'
			.Html::encode('адрес не найден в инвентаризации').'">'
			.Html::encode(Techs::formatMacs($item['mac'])).'</span>';
	}

	$links = [];
	foreach ($item['objects'] as $object) {
		$links[] = ModelWidget::widget(['model' => $object, 'options' => ['static_view' => true]]);
	}
	return implode(', ', $links);
};

?>
<div id="<?= $containerId ?>">
<?php if ($error) { ?>
	<span class="text-secondary opacity-75">опрос не выполнен: <?= Html::encode($error) ?></span>
<?php } elseif ($status === 'pending') { ?>
	<span class="text-secondary">
		<span class="spinner-border spinner-border-sm" role="status"></span>
		&mdash; идёт опрос коммутатора<?= $refreshUrl ? '' : ', откройте карточку позже' ?>
	</span>
<?php } elseif ($status === 'error') { ?>
	<span class="text-secondary opacity-75">опрос не выполнен:
		<?= Html::encode($data['error'] ?? 'ошибка сервиса') ?></span>
<?php } elseif ($failure) { ?>
	<?php /* причина одной строкой, полный текст ошибки - в подсказке */ ?>
	<span class="text-secondary opacity-75"<?= empty($failure['detail']) ? ''
		: ' title="'.Html::encode($failure['detail']).'"' ?>>коммутатор не опрошен:
		<?= Html::encode($failure['error'] ?? 'причина не указана') ?></span>
<?php } elseif (!count($ports)) { ?>
	<span class="text-secondary opacity-75">таблица MAC пуста: коммутатор не видит ни одного адреса</span>
	<?= $this->render('_diagnostics', [
		'diagnostics' => $data['diagnostics'] ?? [],
		'switches' => [$tech->id => $tech],
	]) ?>
<?php } else { ?>
	<table class="table table-sm w-auto mb-1">
		<thead>
			<tr>
				<th>Порт</th>
				<th>VLAN</th>
				<th>Подключено</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($ports as $port) { ?>
			<tr<?= $port['transit'] ? ' class="text-secondary"' : '' ?>>
				<td>
					<?= Html::encode($port['port']) ?>
					<?php if ($port['uplink']) { ?>
						<span class="badge bg-secondary" title="<?= Html::encode(
							'Порт связан с другим коммутатором'
							.(empty($port['uplink_peer']) ? '' : ': '.$port['uplink_peer'])
							.' — за ним сеть, а не устройство') ?>">транзит</span>
					<?php } ?>
				</td>
				<td><?= Html::encode(implode(', ', $port['vlans'])) ?></td>
				<td>
					<?php if ($port['transit'] && !$port['uplink']) { ?>
						<?php /* связей портов нет, но адресов слишком много для
						       «устройство + телефон»: за портом сеть. Куда именно
						       она идёт, покажет карта сети (plans/network-map.md) */ ?>
						<span title="<?= Html::encode('Адресов на порту больше, чем бывает у '
							.'рабочего места: за портом сеть — другой коммутатор, точка доступа '
							.'или неуправляемое устройство') ?>">адресов: <?= (int)$port['count'] ?></span>
					<?php } elseif ($port['transit']) { ?>
						<?= Html::encode(empty($port['uplink_peer'])
							? 'другой коммутатор' : $port['uplink_peer']) ?>
						<span class="small">(адресов: <?= (int)$port['count'] ?>)</span>
					<?php } else { ?>
						<?php $cells = [];
						foreach ($port['macs'] as $item) $cells[] = $macCell($item); ?>
						<?= implode('<br>', $cells) ?>
						<?php if (count($port['macs']) > 1) { ?>
							<?php /* два адреса на порту - штатное дело: телефон с ПК
							       за ним (обычно в разных VLAN), виртуалки, свитч под столом */ ?>
							<span class="text-secondary small" title="<?= Html::encode(
								'Несколько адресов на порту: IP-телефон с включённым в него ПК, '
								.'виртуальные машины или неуправляемый коммутатор') ?>">
								(адресов: <?= (int)$port['count'] ?>)</span>
						<?php } ?>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<div class="text-secondary opacity-75 small">
		портов с адресами: <?= count($ports) ?><?php
		if (!empty($data['duration'])) { ?>, опрос занял <?= (float)$data['duration'] ?> c<?php } ?>
	</div>
<?php } ?>

<?php if ($refreshUrl) { ?>
	<?php /* скрипт живёт ВНУТРИ контейнера: ответ подменяет контейнер целиком
	       (вместе со своим скриптом), поэтому опрос продолжается сам собой */ ?>
	<script>
		setTimeout(function () {
			$.get(<?= json_encode($refreshUrl) ?>, function (data) {
				$('#<?= $containerId ?>').replaceWith(data);
			});
		}, 15000);
	</script>
<?php } ?>
</div>
