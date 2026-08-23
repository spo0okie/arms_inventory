<?php
/**
 * Панель «Порт коммутатора»: по каждому MAC-адресу объекта — на каком
 * порту какого коммутатора он виден (данные сервиса arms.macsearch,
 * коммутаторы — из инвентаризации, поэтому в выдаче они ссылками).
 *
 * Три состояния результата: готов, идёт (pending — панель сама перезапросит
 * себя по $refreshUrl) и ошибка (сервис не ответил; остальные адреса это не
 * прячет). Порты, связанные с другими коммутаторами (аплинки), помечаются:
 * устройство через них видно транзитом.
 */

use app\components\widgets\page\ModelWidget;
use app\models\Techs;
use yii\helpers\Html;

/* @var $results array [['mac'=>string,'data'=>array|null,'error'=>string|null], ...] */
/* @var $refreshUrl string|null URL самоперезапроса панели, пока идёт опрос */
/* @var $switches Techs[] опрошенные коммутаторы (id => модель) */
/* @var $provider \app\components\integrations\providers\MacSearchProvider */
/* @var $compact bool панель рисуется во вложенном списке - нужно плотнее */

$containerId = 'macsearch-'.substr(md5(implode(',', array_column($results, 'mac')).($refreshUrl ?? '')), 0, 12);

//коммутатор ссылкой на карточку (id пришёл из нашего же запроса); адрес
//показываем, только если объект почему-то не нашёлся
$switchLink = static function ($row) use ($switches) {
	$tech = $switches[$row['target'] ?? null] ?? null;
	if (is_object($tech)) return ModelWidget::widget(['model' => $tech, 'options' => ['static_view' => true]]);
	return Html::encode($row['host'] ?? '');
};

//сколько неопрошенных показывать списком: если не отвечает вся площадка,
//портянка на два экрана не нужна
$failedLimit = 10;

?>
<div id="<?= $containerId ?>">
<?php if (!count($results)) { ?>
	<span class="text-secondary opacity-75">нет адресов или коммутаторов для опроса</span>
<?php } ?>

<?php foreach ($results as $result) {
	$data = $result['data'];
	$mac = Techs::formatMacs($result['mac']);
	$rows = $data['rows'] ?? [];
	$failed = $data['errors'] ?? [];
	$stats = $data['targets'] ?? [];
	?>
	<div class="<?= $compact ? 'mb-1' : 'mb-2' ?>">
		<?php if (count($results) > 1 || !count($rows)) { ?>
			<span class="mac_address"><?= Html::encode($mac) ?></span>
		<?php } ?>

		<?php if ($result['error']) { ?>
			<span class="text-secondary opacity-75">&mdash; не найден: <?= Html::encode($result['error']) ?></span>
		<?php } elseif (($data['status'] ?? null) === 'pending') { ?>
			<span class="text-secondary">
				<span class="spinner-border spinner-border-sm" role="status"></span>
				&mdash; идёт опрос коммутаторов<?= $refreshUrl ? '' : ', откройте карточку позже' ?>
			</span>
		<?php } elseif (($data['status'] ?? null) === 'error') { ?>
			<span class="text-secondary opacity-75">&mdash; опрос не выполнен:
				<?= Html::encode($data['error'] ?? 'ошибка сервиса') ?></span>
		<?php } elseif (!count($rows)) { ?>
			<span class="text-secondary opacity-75">&mdash; не найден на портах коммутаторов</span>
			<?php /* «не найден» и «не поняли ответ коммутатора» выглядят одинаково -
			       улики сервиса объясняют, что именно случилось */ ?>
			<?= $this->render('_diagnostics', [
				'diagnostics' => $data['diagnostics'] ?? [],
				'switches' => $switches,
			]) ?>
		<?php } else { ?>
			<table class="table table-sm w-auto mb-1">
				<thead>
					<tr>
						<th>Коммутатор</th>
						<th>VLAN</th>
						<th>Порт</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($rows as $row) { ?>
					<tr<?= empty($row['uplink']) ? '' : ' class="text-secondary"' ?>>
						<td><?= $switchLink($row) ?></td>
						<td><?= Html::encode($row['vlan'] ?? '') ?></td>
						<td>
							<?= Html::encode($row['port'] ?? '') ?>
							<?php if (!empty($row['uplink'])) { ?>
								<span class="badge bg-secondary" qtip_ttip="<?= Html::encode(
									'Порт связан с другим коммутатором'
									.(empty($row['uplink_peer']) ? '' : ': '.$row['uplink_peer'])
									.' — устройство видно через него транзитом') ?>">транзит</span>
							<?php } ?>
							<?php if (!empty($row['port_macs']) && $row['port_macs'] > 1) { ?>
								<?php /* два-три адреса на порту - штатное дело (телефон бриджом
								       с ПК во втором порту, виртуалки, свитч под столом), так что
								       это справка, а не признак транзита */ ?>
								<span class="text-secondary small" qtip_ttip="<?= Html::encode(
									'Штатно: IP-телефон с включённым в него ПК, виртуальные машины, '
									.'неуправляемый коммутатор. Транзит определяется связями портов, '
									.'а не числом адресов') ?>">(адресов на порту: <?= (int)$row['port_macs'] ?>)</span>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		<?php } ?>

		<?php if (!$compact && $failed) { ?>
			<?php /* неопрошенные - такие же объекты инвентаризации, как найденные:
			       к ним надо уметь перейти и починить. Рядом причина одной
			       строкой, полный текст ошибки - в подсказке */ ?>
			<div class="text-secondary small">
				не опрошены (<?= count($failed) ?><?=
					empty($stats['requested']) ? '' : ' из '.(int)$stats['requested'] ?>):
			</div>
			<table class="table table-sm w-auto mb-1 text-secondary">
				<tbody>
				<?php foreach (array_slice($failed, 0, $failedLimit) as $failure) { ?>
					<tr>
						<td><?= $switchLink($failure) ?></td>
						<td<?= empty($failure['detail']) ? ''
							: ' qtip_ttip="'.Html::encode($failure['detail']).'"' ?>><?=
							Html::encode($failure['error'] ?? 'причина не указана') ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php if (count($failed) > $failedLimit) { ?>
				<div class="text-secondary opacity-75 small">
					…и ещё <?= count($failed) - $failedLimit ?>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
<?php } ?>

<?php if ($refreshUrl) { ?>
	<?php /* скрипт живёт ВНУТРИ контейнера: ответ подменяет контейнер целиком
	       (вместе со своим скриптом), поэтому опрос продолжается сам собой */ ?>
	<script>
		//сервис ещё опрашивает: перезапрашиваем панель, пока не дождёмся результата
		//(число попыток ограничено параметром attempt в самом URL)
		setTimeout(function () {
			$.get(<?= json_encode($refreshUrl) ?>, function (data) {
				$('#<?= $containerId ?>').replaceWith(data);
			});
		}, 15000);
	</script>
<?php } ?>
</div>
