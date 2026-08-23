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
use app\components\widgets\page\ModelWidget;
use app\models\Ports;
use app\models\Techs;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model \app\models\Techs устройство, чьи порты выводим */
/* @var $ports array|null результат опроса {@see MacSearchProvider::switchPorts()} */
/* @var $passport array|null паспорт портов с коммутатора (её собственный порядок) */
/* @var $transitFrom int с какого числа адресов порт считается транзитным */
/* @var $this yii\web\View */

if (!isset($ports)) $ports = null;
if (!isset($passport)) $passport = [];
if (!isset($transitFrom)) $transitFrom = 4;

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

//пометки диффа: галочка - подтверждение, кнопка - предложение действия
$marks = [
	'ok' => ['fas fa-check text-secondary', 'Найдено то, что записано'],
	'self' => ['fas fa-exclamation text-warning', 'За портом виден адрес самого коммутатора: '
		.'это служебный порт (CPU) либо петля. Связь коммутатора с самим собой записать нельзя'],
];

//предложения: иконка, подсказка, что делать (do), нужно ли подтверждение
$offers = [
	'replaced' => ['fas fa-redo text-success',
		'На порту другое оборудование - заменить', 'attach', null],
	'added' => ['fas fa-plus text-success',
		'Порт был пуст, оборудование обнаружено - привязать', 'attach', null],
	'foreign' => ['fas fa-times text-danger',
		'Записанное оборудование не отозвалось, а адреса на порту чужие. '
		.'Убрать оборудование с порта?',
		'detach', 'Записанного оборудования на порту не видно, но адреса там есть. '
		.'Оборудование может быть просто выключено, а адреса принадлежать его '
		.'второму интерфейсу. Точно убрать его с порта?'],
	'quiet' => ['fas fa-times text-danger',
		'Адресов на порту не видно - убрать оборудование с порта?',
		'detach', 'Оборудование может быть просто выключено. Точно убрать его с порта?'],
];

//члены каждой группы по данным коммутатора: ярлык ставится на всех сразу, по
//одному он смысла не имеет
$aggregateMembers = [];
$aggregateOffers = 0;
foreach ($rows as $port) {
	if (!strlen($port['aggregate'] ?? '')) continue;
	$aggregateMembers[$port['aggregate']][] = $port['port'];
	//коммутатор собрал порт в группу, а в инвентаризации этого нет
	if (!is_object($port['link'] ?? null) || (string)$port['link']->aggr !== (string)$port['aggregate'])
		$aggregateOffers++;
}

//как порты называет сам коммутатор. Имена портов - свойство экземпляра, а не
//модели: стек перенумеровывает порты, MikroTik позволяет переименовать
//интерфейсы, и модельные Ge0/1..24 после этого фантомы
$renames = [];
$adoptable = !is_null($ports);
foreach ($rows as $port) {
	if (!($port['physical'] ?? true)) continue;
	$real = (string)($port['real'] ?? '');
	//объявленный порт, о котором коммутатор не сказал ничего: взять имена
	//нельзя - список получился бы короче, чем корпус
	if (!strlen($real)) {
		if (!empty($port['declared'])) $adoptable = false;
		continue;
	}
	if ($real !== (string)$port['port']) $renames[] = $port['port'].' → '.$real;
}
//переименовывать нечего - кнопка не нужна; но у устройства без объявленных
//портов вообще взять имена с коммутатора - единственный способ их объявить
if (!count($renames) && count($model->portsTemplate)) $adoptable = false;

//имена и ПОРЯДОК берём из паспорта: он идёт по ifIndex, то есть по железу, а
//строки таблицы уже разложены по объявленному порядку инвентаризации
$scannedNames = [];
foreach ($passport as $item) {
	$name = (string)($item['name'] ?? '');
	if (!strlen($name)) continue;
	//на корпусе существуют только розетки: ifType 6 (ethernetCsmacd). Агрегат,
	//Vlan-интерфейс и loopback портами объявлять нельзя
	if (isset($item['type'])
		? (int)$item['type'] !== 6
		: MacSearchProvider::isAggregate($name)) continue;
	$scannedNames[] = $name;
}
if (!count($scannedNames)) $adoptable = false;

/**
 * Узел на той стороне записанной связи: «Порт X: Устройство», а у порта
 * без имени (устройство привязано без порта) - просто «Устройство».
 * Один рендер на все хопы: первый и последующие выглядят одинаково.
 */
$hop = function (Ports $peer): string {
	if (strlen((string)$peer->name)) {
		return $this->render('/ports/item', ['model' => $peer, 'include_tech' => true, 'reverse' => true]);
	}
	return is_object($peer->tech)
		? ModelWidget::widget(['model' => $peer->tech, 'options' => ['static_view' => true]]) : '';
};

/**
 * Записанная связь целиком: первый хоп, а если на той стороне устройство
 * ровно с двумя объявленными портами и второй его порт тоже связан - следующий
 * хоп тем же рендером, через « : Порт Y → ». Так телефон с ПК за ним читается
 * одной строкой, как и предложение опроса.
 */
$chain = static function (Ports $peer) use ($hop): string {
	$html = $hop($peer);
	$seen = [$peer->id => true];
	while (true) {
		$bridge = $peer->tech;
		if (!is_object($bridge) || count($bridge->portsTemplate) !== 2) break;
		$next = null;
		foreach ($bridge->ports as $other) {
			if ($other->id == $peer->id || !is_object($other->linkPort)) continue;
			$next = $other;
			break;
		}
		//второй порт не связан либо цепочка закольцевалась
		if (is_null($next) || isset($seen[$next->linkPort->id])) break;
		$html .= ' : '.Ports::$port_prefix.Html::encode($next->name).' → '.$hop($next->linkPort);
		$peer = $next->linkPort;
		$seen[$peer->id] = true;
	}
	return $html;
};

//после действия перезапрашиваем панель: вердикты пересчитываются на свежих
//данных инвентаризации, а сам опрос берётся из кэша сервиса
$containerId = 'techs-ports-'.$model->id;
$reload = Url::to(['/integrations/panel', 'provider' => 'macsearch', 'panel' => 'switch',
	'class' => 'techs', 'id' => $model->id, 'refresh' => 1]);

/** Порт на той стороне: один - подставляем, несколько - селект, пусто - без порта */
$peerPick = static function (array $peers, string $field, int $index): array {
	if (count($peers) === 1) {
		return [Html::encode($peers[0]['name']), [
			$field => $peers[0]['id'], $field.'_name' => $peers[0]['id'] ? '' : $peers[0]['name']]];
	}
	//портов у устройства не объявлено - оно привязывается без порта, и в
	//строке это просто устройство, без «Порт …:»
	if (!count($peers)) return ['', []];

	//в связи участвуют две стороны, и гадать, в какой разъём воткнут кабель,
	//мы не имеем права - выбирает человек
	$options = [];
	foreach ($peers as $peer) {
		$options[] = Html::tag('option', Html::encode($peer['name']), [
			'value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
	}
	return [Html::tag('select', implode('', $options), [
		'class' => 'form-select form-select-sm d-inline-block w-auto py-0 port-scan-peer',
		'data-field' => $field, 'data-proposal' => $index, 'qtip_ttip' => 'Порт на той стороне',
	]), []];
};

/**
 * Предложение привязки: строка "? что куда" для ячейки соединения и кнопка
 * для последней колонки. Цепочка - два звена: порт → мост, мост → лист.
 */
$proposalLine = static function (array $port, array $proposal, int $index) use ($model, $peerPick) {
	$device = $proposal['device'];
	$data = ['tech' => $model->id, 'port' => $port['port'], 'do' => 'attach',
		'device' => $device->id, 'proposal' => $index];

	//у моста в цепочке оба порта идут селектом-переключателем даже при
	//"очевидном" выборе: какой из двух смотрит в коммутатор, знает человек
	$peers = $proposal['peers'];
	if (is_array($proposal['chain'] ?? null) && count($peers) === 2) {
		$options = [];
		foreach ($peers as $peer) {
			$options[] = Html::tag('option', Html::encode($peer['name']), [
				'value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
		}
		$peerHtml = Html::tag('select', implode('', $options), [
			'class' => 'form-select form-select-sm d-inline-block w-auto py-0 port-scan-peer',
			'data-field' => 'peer', 'data-proposal' => $index, 'data-toggle-via' => 1,
			'qtip_ttip' => 'Порт моста к коммутатору; второй уйдёт к устройству за ним',
		]);
		$fields = [];
	} else {
		[$peerHtml, $fields] = $peerPick($peers, 'peer', $index);
	}
	$data += $fields;
	$line = (strlen($peerHtml) ? Ports::$port_prefix.$peerHtml.': ' : '')
		.ModelWidget::widget(['model' => $device, 'options' => ['static_view' => true]]);
	$title = 'Привязать '.$device->name;

	if (is_array($proposal['chain'] ?? null)) {
		$chain = $proposal['chain'];
		$data['do'] = 'chain';
		$data['via'] = $chain['via']['id'];
		$data['via_name'] = $chain['via']['id'] ? '' : $chain['via']['name'];
		$data['leaf'] = $chain['leaf']->id;
		[$leafHtml, $leafFields] = $peerPick($chain['leaf_peers'], 'leaf_peer', $index);
		$data += $leafFields;
		$line .= ' : '.Ports::$port_prefix.'<span class="port-scan-via" data-proposal="'.$index.'">'
			.Html::encode($chain['via']['name']).'</span> → '
			.(strlen($leafHtml) ? Ports::$port_prefix.$leafHtml.': ' : '')
			.ModelWidget::widget(['model' => $chain['leaf'], 'options' => ['static_view' => true]]);
		$title = 'Привязать '.$device->name.', а за ним - '.$chain['leaf']->name;
	}

	//цепочка - это «связать»: плюс обещал бы добавление, а тут записанное
	//может остаться на месте, меняется схема соединения
	$icon = is_array($proposal['chain'] ?? null) ? 'fas fa-link'
		: ($port['verdict'] === 'replaced' ? 'fas fa-redo' : 'fas fa-plus');
	$button = Html::button('<i class="'.$icon.' text-success"></i>', [
		'class' => 'btn btn-sm btn-link p-0 d-block',
		'qtip_ttip' => $title.($port['verdict'] === 'replaced' ? ' вместо записанного' : ''),
		'data-scan' => json_encode($data),
		'data-proposal' => $index,
		'onclick' => 'portsScanApply(this)',
	]);
	return ['<span class="text-success" qtip_ttip="'.Html::encode($title).'">?</span> '.$line, $button];
};

/** Кнопка "убрать": нагрузка в data-атрибутах, поведение - в общем скрипте */
$offer = static function (array $port, array $offerData) use ($model) {
	[$icon, $hint, $do, $confirm] = $offerData;
	return Html::button('<i class="'.$icon.'"></i>', [
		'class' => 'btn btn-sm btn-link p-0',
		'qtip_ttip' => $hint,
		'data-scan' => json_encode(['tech' => $model->id, 'port' => $port['port'], 'do' => $do]),
		'data-confirm' => $confirm,
		'onclick' => 'portsScanApply(this)',
	]);
};

?>
<?php /* карта портов: где физически находится порт. Рисуется, только если у
       модели описана геометрия корпуса; состояния берутся из того же
       результата опроса, что и таблица */ ?>
<?= \app\components\PortsMapWidget::widget([
	'model' => $model,
	'ports' => is_null($ports) ? [] : $ports,
]) ?>

<?php
//колонка агрегации нужна, только если группы вообще есть: у большинства
//железок её содержимое пусто, а место она занимает
$hasAggregates = false;
foreach ($rows as $port) {
	if (!empty($port['aggregate']) || (is_object($port['link'] ?? null)
		&& strlen((string)$port['link']->aggr))) $hasAggregates = true;
}
?>
<table class="table table-striped table-sm">
	<tr>
		<th>Порт</th>
		<?php if ($hasAggregates) { ?><th>Агрегация</th><?php } ?>
		<th>Пояснение</th>
		<th colspan="3">Соединение с</th>
		<th></th>
	</tr>

<?php
//сколько расхождений и сколько из них можно принять одной кнопкой
$offered = $aggregateOffers;
foreach ($rows as $port) if (isset($offers[$port['verdict']])) $offered++;
//разом принимаются только безопасные находки: привязка и объявление агрегата
$acceptable = $aggregateOffers;
foreach ($rows as $port) if (count($port['proposals'] ?? []) === 1) $acceptable++;
?>
<?php foreach ($rows as $port) {
	$link = $port['link'];
	$verdict = $port['verdict'];
	?>
	<tr>
		<td>
			<?= is_object($link)
				? $this->render('/ports/item', ['model' => $link, 'return' => 'previous', 'modal' => true])
				: Html::a(Ports::$port_prefix.$port['port'], ['/ports/create',
					'return' => 'previous',
					'Ports[name]' => $port['port'],
					'Ports[comment]' => $port['comment'],
					'Ports[techs_id]' => $model->id,
				], ['class' => 'open-in-modal-form', 'data-reload-page-on-submit' => 1]) ?>

			<?php if (count($port['vlans'])) { ?>
				<?php /* настроенные VLAN приходят с коммутатора (паспорт портов);
				       если паспорта нет - показываем те, где замечен трафик, и
				       говорим об этом в подсказке. Нетегированный выделен */ ?>
				<br><small class="text-secondary opacity-75" qtip_ttip="<?= Html::encode($port['vlans_configured']
					? 'VLAN, настроенные на порту (жирным - нетегированный)'
					: 'VLAN, в которых на этом порту замечены адреса') ?>">VLAN <?php
					$names = [];
					foreach ($port['vlans'] as $vlan) {
						//паспорт даёт структуру, таблица MAC - просто номер
						$number = is_array($vlan) ? $vlan['vlan'] : $vlan;
						$names[] = is_array($vlan) && $vlan['untagged']
							? '<b>'.Html::encode($number).'</b>' : Html::encode($number);
					}
					echo implode(', ', $names); ?></small>
			<?php } ?>

		</td>

		<?php if ($hasAggregates) { ?>
			<td class="text-nowrap">
				<?php
				$declaredAgg = is_object($link) ? (string)$link->aggr : '';
				$scannedAgg = (string)$port['aggregate'];
				?>
				<?php if (strlen($declaredAgg)) { ?>
					<?= Html::encode($declaredAgg) ?>
					<?php if ($scannedAgg !== '' && $scannedAgg !== $declaredAgg) { ?>
						<span class="text-danger small" qtip_ttip="<?= Html::encode(
							'На коммутаторе порт собран в '.$scannedAgg) ?>">≠ <?= Html::encode($scannedAgg) ?></span>
					<?php } elseif ($scannedAgg === '' && !is_null($ports)) { ?>
						<span class="text-danger small" qtip_ttip="<?= Html::encode(
							'На коммутаторе порт ни в какую группу не собран') ?>">≠</span>
					<?php } ?>
				<?php } ?>
				<?php if (strlen($scannedAgg) && $scannedAgg !== $declaredAgg) { ?>
					<?php /* коммутатор говорит, что порт в группе, а в инвентаризации
					       не так: ставим ярлык всем её портам одним кликом */ ?>
					<?php $members = $aggregateMembers[$scannedAgg] ?? [$port['port']]; ?>
					<?php if (!strlen($declaredAgg)) { ?>
						<span class="text-secondary"><?= Html::encode($scannedAgg) ?></span>
					<?php } ?>
					<?= Html::button('<i class="fas fa-object-group text-success"></i>', [
						'class' => 'btn btn-sm btn-link p-0 port-scan-accept',
						'qtip_ttip' => 'Пометить как '.$scannedAgg.' порты: '.implode(', ', $members),
						'data-scan' => json_encode([
							'tech' => $model->id,
							'do' => 'aggregate',
							'aggregate' => $port['aggregate'],
							'members' => implode("\n", $members),
						]),
						'onclick' => 'portsScanApply(this)',
					]) ?>
				<?php } ?>
			</td>
		<?php } ?>
		<td><?= Html::encode(is_object($link) ? (string)$link->comment : $port['comment']) ?>
			<?php if (strlen($port['description'])) { ?>
				<?php /* подпись, которую сделал сетевик на самом коммутаторе: латиница,
				       обрывки - но человеку полезно. Наш комментарий не трогаем и
				       дополнять им не предлагаем: это две разные записи об одном
				       порте, и сводить их вправе только человек */ ?>
				<br><small class="text-secondary opacity-75" qtip_ttip="<?= Html::encode(
					'Описание порта на самом коммутаторе') ?>"><?= Html::encode($port['description']) ?></small>
			<?php } ?>
		</td>

		<?php
		//записанное перечёркиваем только когда на порту достоверно другое
		$outdated = $verdict === 'replaced' ? ' class="text-secondary text-decoration-line-through"' : '';

		//что опрос увидел на порту - в той же ячейке, под записанным: это два
		//утверждения об одном и том же кабеле, и читать их надо рядом, а не
		//через строку таблицы
		$extra = [];
		$buttons = [];
		//предложения привязки: строка "? что куда" здесь, кнопка - в последней
		//колонке. Найденное перечислять отдельно незачем - оно и есть предложение
		foreach ($port['proposals'] ?? [] as $index => $proposal) {
			[$line, $button] = $proposalLine($port, $proposal, $index);
			$extra[] = $line;
			$buttons[] = $button;
		}
		if (!count($port['proposals'] ?? [])
			&& in_array($verdict, ['replaced', 'added', 'foreign', 'seen'], true)) {
			$seen = [];
			foreach ($port['found'] as $device) {
				$seen[] = ModelWidget::widget(['model' => $device, 'options' => ['static_view' => true]]);
			}
			//адрес, за которым в инвентаризации никого: показываем как есть -
			//может пригодиться, а прятать факт незачем
			foreach ($port['macs'] as $item) {
				if (count($item['objects'])) continue;
				$seen[] = '<span class="mac_address" qtip_ttip="'
					.Html::encode('Такой MAC-адрес в инвентаризации не найден').'">'
					.Html::encode(Techs::formatMacs($item['mac'])).'</span>';
			}
			if (count($seen)) $extra[] = '<small class="text-secondary">на порту видно:</small> '
				.implode(', ', $seen);
		}

		//сосед по LLDP/CDP - факт с коммутатора, а не догадка по адресам: именно он
		//и есть настоящая связь коммутатор-коммутатор
		if (count($port['neighbors'])) {
			$seenNeighbors = [];
			foreach ($port['neighbors'] as $neighbor) {
				$title = trim(($neighbor['remote_name'] ?? '').' '.($neighbor['remote_port'] ?? ''));
				$seenNeighbors[] = '<span qtip_ttip="'.Html::encode(
					'Протокол: '.($neighbor['protocol'] ?? '?')
					.($neighbor['remote_mac'] ? ', адрес '.$neighbor['remote_mac'] : '')
					).'">'.Html::encode($title ?: ($neighbor['remote_mac'] ?? '?')).'</span>';
			}
			$extra[] = '<small class="text-secondary">сосед'
				.(count($port['neighbors']) > 1 ? 'и' : '').':</small> '
				.implode(', ', $seenNeighbors);
		}
		//в занятой ячейке находки идут новой строкой под записанным, в пустой -
		//начинают её собой
		$extraLines = implode('<br>', $extra);
		$extra = strlen($extraLines) ? '<br>'.$extraLines : '';
		?>
		<?php if (is_object($link) && is_object($link->linkPort)) { ?>
			<td><span class="fas fa-exchange-alt"></span></td>
			<td><?= Html::encode((string)$link->linkPort->comment) ?></td>
			<td>
				<span<?= $outdated ?>><?= $chain($link->linkPort) ?></span>
				<?= $extra ?>
			</td>
		<?php } elseif (is_object($link) && is_object($link->linkTech)) { ?>
			<td><span class="fas fa-exchange-alt"></span></td><td></td>
			<td>
				<span<?= $outdated ?>><?= ModelWidget::widget(['model' => $link->linkTech]) ?></span>
				<?= $extra ?>
			</td>
		<?php } else { ?>
			<td colspan="3"><?php
				//интерфейс, которого на корпусе нет: агрегат, VLAN-интерфейс,
				//loopback. Называть его свободным портом нельзя - воткнуть в
				//него ничего не получится, розетки не существует
				if (!($port['physical'] ?? true)) echo '<span class="text-secondary opacity-75" '
					.'qtip_ttip="'.Html::encode('Интерфейс существует только в настройках '
					.'коммутатора: агрегат, VLAN-интерфейс, loopback - розетки для него нет')
					.'">нет на корпусе</span>';
				//ни записи, ни адресов: «свободен» сказать нельзя - кабель может быть
				//воткнут, а та сторона выключена. Линка нет - вот что известно
				elseif ($verdict === 'free') echo '<span class="text-secondary opacity-75" qtip_ttip="'
					.Html::encode('Ничего не записано, и адресов на порту не видно. Это не значит, что порт '
					.'свободен: кабель может быть воткнут, а устройство выключено').'">линка нет</span>';
				elseif ($verdict === 'disabled') echo '<span class="text-secondary opacity-75">'
					.'выключен на коммутаторе</span>';
				//подпись состояния и находки - разными строками
				echo in_array($verdict, ['free', 'disabled'], true) && strlen($extraLines) ? '<br>' : '';
				echo $extraLines;
			?></td>
		<?php } ?>

		<td class="text-nowrap">
			<?php if ($verdict === 'disabled') { ?>
				<span class="text-secondary small" qtip_ttip="<?= Html::encode(
					'Порт выключен администратором на самом коммутаторе') ?>">выключен</span>
			<?php } elseif (isset($marks[$verdict]) && !count($buttons)) { ?>
				<?php [$icon, $hint] = $marks[$verdict]; ?>
				<i class="<?= $icon ?>" qtip_ttip="<?= Html::encode($hint) ?>"></i>
			<?php } elseif (count($buttons)) { ?>
				<?php /* одно предложение принимается и разом; из нескольких
				       выбирает человек, и "принять все" их не трогает */ ?>
				<div class="<?= count($buttons) === 1 ? 'port-scan-accept' : '' ?>"><?= implode('', $buttons) ?></div>
			<?php } elseif (isset($offers[$verdict])) { ?>
				<?= $offer($port, $offers[$verdict]) ?>
			<?php } elseif ($verdict === 'transit') { ?>
				<?php /* транзит обязан объяснить себя: откуда вывод и что с ним
				       делать. Два источника: записанная связь с другим коммутатором
				       либо просто много адресов */ ?>
				<?php
				$why = $port['uplink']
					? 'Порт связан с коммутатором '.$port['uplink_peer'].': адреса за ним '
						.'('.(int)$port['count'].') принадлежат устройствам той стороны, '
						.'сравнивать их с этим портом бессмысленно'
					: 'На порту '.(int)$port['count'].' адресов - столько у одного '
						.'устройства не бывает, за портом сеть (коммутатор, хаб, точка '
						.'доступа). Порог: '.(int)$transitFrom.'. Кто там стоит, '
						.'коммутатор не сказала (сосед по LLDP не найден): привяжите '
						.'коммутатор руками через форму порта. Адреса - в блоке '
						.'"показать данные с коммутатора"';
				?>
				<span class="badge bg-secondary" qtip_ttip="<?= Html::encode($why) ?>">транзит</span>
			<?php } ?>
		</td>
	</tr>

<?php } ?>
</table>

<?php if (!is_null($ports)) { ?>
	<?php /* итог опроса: сколько расхождений и когда снимали данные. Без
	       отметки времени непонятно, почему устройство "не отозвалось" -
	       может, его выключили пять минут назад, а может, таблица вчерашняя */ ?>
	<div class="text-secondary small mb-2">
		<?php if ($offered) { ?>
			расхождений: <?= $offered ?>.
		<?php } else { ?>
			расхождений нет.
		<?php } ?>
		опрошено в <?= date('H:i') ?>

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
			//ячейке той же строки таблицы: ищем по номеру предложения
			element.closest('tr').find('.port-scan-peer[data-proposal="' + element.data('proposal') + '"]')
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

		//разом - только привязка: снятие связи опаснее и подтверждается поштучно
		window.portsScanAcceptAll = function () {
			var buttons = $('#<?= $containerId ?>').find('.port-scan-accept')
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
			$(this).closest('tr').find('.port-scan-via[data-proposal="' + $(this).data('proposal') + '"]')
				.text($(this).find('option:not(:selected)').first().text());
		});

		window.portsScanReload = function () {
			$('#<?= $containerId ?>').load(<?= json_encode($reload) ?>);
		};
	</script>
<?php } ?>
