<?php
/**
 * Тело карты сети: схема + списки. Общий для первой загрузки и для ответа
 * сверки с сетью (перерисовывается целиком со слоем находок).
 */

use app\components\widgets\page\ModelWidget;
use app\models\Ports;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $map \app\components\NetworkMap */
/* @var $site \app\models\Places */
/* @var $provider \app\components\integrations\providers\MacSearchProvider|null */
/* @var $scanStamp string|null отметка опроса, если карта со слоем сверки */

$overlay = $map->overlay;
$switches = [];
foreach ($map->nodes as $node) foreach ($node['members'] as $member) $switches[$member->id] = $member;

//кандидаты для «это коммутатор…»: все коммутаторы инвентаризации - сосед
//может оказаться и с другой площадки (записан не там или переехал)
$assignable = [];
if (is_array($overlay) && count($overlay['unknown'])) {
	foreach (\app\models\Techs::find()
		->joinWith(['model.type', 'state'], true)
		->where(['tech_types.code' => \app\components\NetworkMap::switchTypes()])
		->andWhere(['or', ['tech_states.archived' => 0], ['tech_states.archived' => null]])
		->orderBy('techs.num')->all() as $candidate) {
		$assignable[$candidate->id] = $candidate->name;
	}
}

/** Коммутатор ссылкой */
$device = fn($tech) => ModelWidget::widget(['model' => $tech, 'options' => ['static_view' => true]]);

/** Порт на той стороне для записи: один - подставляем, неоднозначно - селект */
$peerPick = static function (array $resolved, string $field, int $index) {
	if (count($resolved['candidates'])) {
		$options = [];
		foreach ($resolved['candidates'] as $peer) {
			$options[] = Html::tag('option', Html::encode($peer['name']),
				['value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
		}
		return [Html::tag('select', implode('', $options), [
			'class' => 'form-select form-select-sm d-inline-block w-auto py-0 map-scan-peer',
			'data-field' => $field, 'data-finding' => $index,
			'qtip_ttip' => 'Какой это порт, по имени из LLDP однозначно не понять — выберите',
		]), []];
	}
	if (is_null($resolved['name'])) return ['', []];
	return [Ports::$port_prefix.Html::encode($resolved['name']).': ', [
		$field => $resolved['id'], $field.'_name' => $resolved['id'] ? '' : $resolved['name']]];
};
?>
<?php if (is_array($overlay)) { ?>
	<div class="text-secondary small mb-2">
		<?= Html::encode((string)$scanStamp) ?>;
		подтверждено: <?= count($overlay['confirmed']) ?>,
		не видно: <?= count($overlay['unseen']) ?>,
		найдено незаписанных: <?= count($overlay['found']) ?>,
		неопознанных соседей: <?= count($overlay['unknown']) ?><?php
		if (count($overlay['failed'])) { ?>, не ответили: <?= count($overlay['failed']) ?><?php }
		//фильтры не молчат: сколько строк убрано как телефоны и как пустые
		//LLDP-записи - видно, что карта не «потеряла» их втихую
		if (!empty($overlay['ignored'])) { ?>,
			<span qtip_ttip="IP-телефоны и прочие соседи, отфильтрованные шаблоном neighborIgnore">
				телефонов отсеяно: <?= (int)$overlay['ignored'] ?></span><?php }
		if (!empty($overlay['noise'])) { ?>,
			<span qtip_ttip="Записи LLDP без имени и адреса — чинить не по чему, обычно это застарелые строки таблицы">
				пустых записей: <?= (int)$overlay['noise'] ?></span><?php }
		if (!empty($overlay['endpoints'])) { ?>,
			<span qtip_ttip="Опознанные телефоны/ПК/серверы за портами — они видны в карточках коммутаторов, карта их не рисует">
				оконечных устройств: <?= (int)$overlay['endpoints'] ?></span><?php }
		if (!empty($overlay['confirmed_outside'])) { ?>,
			<span qtip_ttip="Записанные аплинки на коммутаторы других площадок, подтверждённые сверкой">
				подтверждено аплинков: <?= (int)$overlay['confirmed_outside'] ?></span><?php } ?>
	</div>
<?php } ?>

<div class="border rounded p-2 mb-2" style="overflow:auto">
	<div id="network-map-diagram" class="mermaid"><?= Html::encode($map->mermaid()) ?></div>
</div>
<?php /* легенда: карта обязана отвечать на вопрос «это уже записано или это
       находка?» с одного взгляда, без чтения таблиц */ ?>
<div class="text-secondary small mb-3">
	<b>сплошная линия</b> — записано в инвентаризации<?php if (is_array($overlay)) { ?>
		(<span class="text-warning">жёлтая</span> — записано, но сверка не видит);
		<span style="color:#198754"><b>зелёный пунктир</b></span> — найдено сверкой, не записано;
		<b>серый пунктир и «?»</b> — неопознанный сосед<?php } ?>;
	<span class="text-danger">красная рамка</span> — связь закреплена за неработающим
	(статус без флага «в работе»): либо снять связь, либо поправить статус
</div>

<?php if (is_array($overlay) && count($overlay['found'])) { ?>
	<h4>Найдено, не записано</h4>
	<div class="text-secondary small mb-1">
		коммутаторы видят друг друга по LLDP/CDP, а связи в инвентаризации нет;
		🔗 записывает порт↔порт, «записать всё» — только однозначные
		<?= Html::button('Записать всё однозначное', ['class' => 'btn btn-sm btn-outline-success ms-2',
			'onclick' => 'mapScanAcceptAll()']) ?>
	</div>
	<table class="table table-sm table-striped w-auto">
		<tbody>
		<?php foreach ($overlay['found'] as $index => $found) {
			[$peerHtml, $fields] = $peerPick($found['peer'], 'peer', $index);
			$data = ['tech' => $found['a']->id, 'port' => $found['port'], 'do' => 'attach',
				'device' => $found['b']->id] + $fields;
			$unambiguous = !count($found['peer']['candidates']);
			$conflicts = array_filter([$found['conflict'], $found['conflict_remote'] ?? null]);
			?>
			<tr>
				<td><?= $device($found['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($found['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $peerHtml ?><?= $device($found['b']) ?>
					<?php if (is_null($found['peer']['name']) && !count($found['peer']['candidates'])) { ?>
						<span class="text-secondary small" qtip_ttip="<?= Html::encode(
							'Портов у модели не объявлено — связь запишется без порта. Сосед назвал порт: '
							.$found['lldp_port']) ?>">без порта</span>
					<?php } ?>
				</td>
				<td class="text-secondary small"><?= Html::encode($found['protocol']) ?></td>
				<td>
					<?php foreach ($conflicts as $conflict) { ?>
						<span class="text-warning small d-block" qtip_ttip="<?= Html::encode(
							'На одном из портов уже записано другое соединение — запись заменит его') ?>">
							вместо <?= $this->render('/ports/item', ['model' => $conflict,
								'include_tech' => true, 'reverse' => true]) ?></span>
					<?php } ?>
				</td>
				<td><?= Html::button('<i class="fas fa-link text-success"></i>', [
					'class' => 'btn btn-sm btn-link p-0'.($unambiguous && !count($conflicts) ? ' map-scan-accept' : ''),
					'qtip_ttip' => 'Записать связь '.$found['a']->name.' '.$found['port'].' ↔ '.$found['b']->name,
					'data-scan' => json_encode($data), 'data-finding' => $index,
					'onclick' => 'mapScanApply(this)',
				]) ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (count($map->edges)) { ?>
	<h4>Связи</h4>
	<table class="table table-sm table-striped w-auto">
		<thead><tr><th>Коммутатор</th><th>Порт</th><th></th><th>Порт</th><th>Коммутатор</th><th>Группа</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($map->edges as $key => $edge) { ?>
			<?php foreach ($edge['links'] as $link) { ?>
				<?php
				//связь, закреплённая за неработающим, - ошибка документации:
				//либо статус врёт, либо связь пора снять
				$dead = [];
				foreach ([$link['port']->tech, $link['peer']->tech] as $side) {
					if (is_object($side) && is_object($side->state) && !$side->state->operating)
						$dead[] = $side;
				}
				?>
				<tr<?= count($dead) ? ' class="table-warning"' : '' ?>>
					<td><?= $device($link['port']->tech) ?></td>
					<td><?= $this->render('/ports/item', ['model' => $link['port']]) ?></td>
					<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
					<td><?= $this->render('/ports/item', ['model' => $link['peer']]) ?></td>
					<td><?= $device($link['peer']->tech) ?></td>
					<td class="text-secondary"><?= Html::encode($edge['aggr']) ?>
						<?php foreach ($dead as $side) { ?>
							<?php /* снять можно тут же - или починить статус в карточке */ ?>
							<span class="text-danger small" qtip_ttip="<?= Html::encode(
								'Связь закреплена, а '.$side->name.' в статусе «'.$side->state->name
								.'» (не в работе): либо статус врёт, либо связь пора снять') ?>">⚠ <?=
								Html::encode($side->state->name) ?></span>
							<?= Html::button('<i class="fas fa-times text-danger"></i>', [
								'class' => 'btn btn-sm btn-link p-0',
								'qtip_ttip' => 'Снять связь с неработающим '.$side->name,
								'data-scan' => json_encode(['tech' => $link['port']->techs_id,
									'port' => $link['port']->name, 'do' => 'detach']),
								'data-confirm' => $side->name.' в статусе «'.$side->state->name
									.'». Снять записанную связь?',
								'onclick' => 'mapScanApply(this)',
							]) ?>
						<?php } ?>
					</td>
					<td class="text-nowrap"><?php if (is_array($overlay)) {
						if (isset($overlay['confirmed'][$key])) { ?>
							<i class="fas fa-check text-secondary" qtip_ttip="Коммутаторы видят друг друга по LLDP/CDP"></i>
						<?php } elseif (isset($overlay['unseen'][$key])) { ?>
							<i class="fas fa-exclamation text-warning" qtip_ttip="<?= Html::encode(
								'Связь записана, но по LLDP/CDP её не видно ни с одной из ответивших сторон: '
								.'порт выключен, LLDP отключён или кабель переставили') ?>"></i>
							<?php /* починка карты руками: снять неподтверждённую связь можно
							       прямо отсюда - с тем же предупреждением, что в карточке */ ?>
							<?= Html::button('<i class="fas fa-times text-danger"></i>', [
								'class' => 'btn btn-sm btn-link p-0',
								'qtip_ttip' => 'Снять связь '.$link['port']->tech->name.' '.$link['port']->name
									.' ↔ '.$link['peer']->tech->name.' '.$link['peer']->name,
								'data-scan' => json_encode(['tech' => $link['port']->techs_id,
									'port' => $link['port']->name, 'do' => 'detach']),
								'data-confirm' => 'По LLDP связь не видна, но коммутатор может быть просто '
									.'выключен, а LLDP - отключён. Точно снять связь?',
								'onclick' => 'mapScanApply(this)',
							]) ?>
						<?php } else { ?>
							<span class="text-secondary small" qtip_ttip="Ни одна из сторон не ответила на опрос">—</span>
						<?php } } ?></td>
				</tr>
			<?php } ?>
		<?php } ?>
		</tbody>
	</table>
<?php } else { ?>
	<div class="text-secondary mb-3">
		Связей между коммутаторами не записано. Они появляются отсюда («Сверить с
		сетью» → записать найденное), из карточек коммутаторов («Опросить порты»)
		либо руками в форме порта.
	</div>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['unknown'])) { ?>
	<h4>Неопознанные соседи</h4>
	<div class="text-secondary small mb-1">
		коммутатор видит соседа, а в инвентаризации такой не опознан — ни по
		адресу, ни по имени, ни по IP. Как чинить: если это коммутатор из
		инвентаризации — впишите имя из колонки «кто на той стороне» в hostname
		его карточки (или адрес в поле IP), и следующая сверка его опознает;
		если устройства в инвентаризации нет — заведите его. Телефоны сюда не
		попадают (отсеиваются шаблоном), записи без имени и адреса — тоже
	</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($overlay['unknown'] as $unknown) { $row = $unknown['row']; ?>
			<tr>
				<td><?= $device($unknown['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($unknown['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= Html::encode(trim((string)($row['remote_name'] ?? '')) ?: '?') ?>
					<?php if (!empty($row['remote_mac'])) { ?>
						<span class="mac_address small"><?= Html::encode(\app\models\Techs::formatMacs($row['remote_mac'])) ?></span>
					<?php } ?>
					<?php if (!empty($row['remote_port'])) { ?>
						<span class="text-secondary small">порт <?= Html::encode($row['remote_port']) ?></span>
					<?php } ?>
					<?php if (($unknown['count'] ?? 1) > 1) { ?>
						<span class="text-secondary small" qtip_ttip="<?= Html::encode(
							'Столько записей об этом соседе в таблице LLDP; показана одна') ?>">×<?= (int)$unknown['count'] ?></span>
					<?php } ?>
				</td>
				<td class="text-secondary small"><?= Html::encode($row['protocol'] ?? '') ?></td>
				<td>
					<?php /* починка не покидая страницы: выбрать карточку - и то,
					       чем сосед представился, допишется в неё (имя в пустой
					       hostname, адрес в MAC). Следующая сверка опознает */ ?>
					<select class="form-select form-select-sm d-inline-block w-auto py-0 map-assign-tech">
						<option value="">это коммутатор…</option>
						<?php foreach ($assignable as $id => $label) { ?>
							<option value="<?= $id ?>"><?= Html::encode($label) ?></option>
						<?php } ?>
					</select>
					<?= Html::button('<i class="fas fa-link text-success"></i>', [
						'class' => 'btn btn-sm btn-link p-0',
						'qtip_ttip' => 'Дописать имя/адрес этого соседа в выбранную карточку и сверить заново',
						'data-assign' => json_encode(['name' => trim((string)($row['remote_name'] ?? '')),
							'mac' => (string)($row['remote_mac'] ?? '')]),
						'onclick' => 'mapAssign(this)',
					]) ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['outside'])) { ?>
	<h4>Соседи вне карты</h4>
	<div class="text-secondary small mb-1">опознаны, но это не коммутатор этой площадки</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($overlay['outside'] as $item) { ?>
			<tr>
				<td><?= $device($item['a']) ?></td>
				<td><?= Ports::$port_prefix.Html::encode($item['port']) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $device($item['b']) ?> <span class="text-secondary small">порт
					<?= Html::encode($item['row']['remote_port'] ?? '') ?></span></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (is_array($overlay) && count($overlay['failed'])) { ?>
	<h4>Не ответили</h4>
	<table class="table table-sm w-auto text-secondary">
		<tbody>
		<?php foreach ($overlay['failed'] as $failure) { ?>
			<tr>
				<td><?= isset($switches[$failure['target'] ?? 0]) ? $device($switches[$failure['target']])
					: Html::encode($failure['host'] ?? '') ?></td>
				<td<?= empty($failure['detail']) ? '' : ' qtip_ttip="'.Html::encode($failure['detail']).'"' ?>><?=
					Html::encode($failure['error'] ?? 'причина не указана') ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php if (count($map->outside)) { ?>
	<h4>Аплинки на другие площадки</h4>
	<div class="text-secondary small mb-1">
		та сторона — коммутатор другой площадки; на схеме не рисуется. Связи с
		серверами и прочим оборудованием здесь не перечисляются — их место в
		карточках коммутаторов
	</div>
	<table class="table table-sm w-auto">
		<tbody>
		<?php foreach ($map->outside as $link) { ?>
			<tr>
				<td><?= $this->render('/ports/item', ['model' => $link['port'], 'include_tech' => true, 'reverse' => true]) ?></td>
				<td class="text-secondary"><span class="fas fa-exchange-alt"></span></td>
				<td><?= $this->render('/ports/item', ['model' => $link['peer'], 'include_tech' => true, 'reverse' => true]) ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
