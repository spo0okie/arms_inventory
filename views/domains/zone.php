<?php
/**
 * Карта зоны: все имена домена вперемешку из трёх источников — hostname ОС,
 * hostname оборудования и DNS-имена (plans/access-chains.md, итерация 1).
 *
 * Список внешних имён (например, под мониторинг TLS) — это карточка внешней
 * зоны: отдельного признака «внешний» у зоны нет, доменов мало.
 *
 * Связи грузятся жадно: карта рендерится на каждую ОС/железку/имя зоны, цепочка
 * relations на каждой строке дала бы N+1 по адресам и сетям.
 *
 * @var yii\web\View $this
 * @var app\models\Domains $model
 * @var bool $static_view
 */

use app\components\ExpandableCardWidget;
use app\components\widgets\page\ModelWidget;
use app\models\Comps;
use app\models\DnsNames;
use app\models\Techs;
use yii\helpers\Html;

if (!isset($static_view)) $static_view = false;

//источники: тип (иконка + подпись), ключ сортировки — имя в зоне, объект
$rows = [];

/** @var Comps[] $comps */
$comps = $model->getComps()->with(['netIps.network', 'sandbox'])->all();
foreach ($comps as $comp) $rows[] = [
	'sort' => mb_strtolower($comp->name),
	'type' => ['fas fa-desktop', Comps::$title],
	'item' => $this->render('/comps/item', ['model' => $comp, 'static_view' => true, 'show_ips' => true]),
];

/** @var Techs[] $techs */
$techs = $model->getTechs()->with(['netIps.network', 'state'])->all();
foreach ($techs as $tech) if (trim((string)$tech->hostname) !== '') $rows[] = [
	'sort' => mb_strtolower((string)$tech->hostname),
	'type' => ['fas fa-server', Techs::$title],
	'item' => $this->render('/techs/item', [
		'model' => $tech, 'name' => mb_strtolower((string)$tech->hostname),
		'static_view' => true, 'show_ips' => true,
	]),
];

/** @var DnsNames[] $names */
$names = $model->getDnsNames()->with(['netIps.network'])->all();
foreach ($names as $name) {
	$ips = [];
	foreach ($name->netIps as $ip) $ips[] = ModelWidget::widget(['model' => $ip, 'options' => ['static_view' => true]]);
	$rows[] = [
		'sort' => mb_strtolower($name->host),
		'type' => ['fas fa-at', DnsNames::$title],
		//в карте зоны суффикс зоны — шум: показываем имя в зоне, у apex — полное имя
		'item' => $this->render('/dns-names/item', [
			'model' => $name, 'static_view' => true,
			'name' => $name->isApex ? $name->fqdn : $name->host,
		])
			. (count($ips) ? ': ' . implode(', ', $ips) : ''),
	];
}

if (!count($rows)) return;

usort($rows, static fn($a, $b) => strcmp($a['sort'], $b['sort']));

$lines = [];
foreach ($rows as $row) {
	[$icon, $title] = $row['type'];
	$lines[] = Html::tag('span', '', ['class' => $icon . ' small opacity-75 me-1', 'qtip_ttip' => $title, 'qtip_side' => 'right'])
		. $row['item'];
}
?>
<h4 class="mt-3">
	Имена в зоне
	<span class="small opacity-75" qtip_ttip="Все имена зоны вперемешку: hostname ОС, hostname оборудования и DNS-имена.<br>Иконка перед именем — тип записи" qtip_side="bottom">
		(<?= count($rows) ?>)
	</span>
</h4>
<?= ExpandableCardWidget::widget([
	'content' => implode('<br />', $lines),
	//короткая карта раскрыта сразу, длинная (корпоративный домен) — свёрнута
	'initialExpand' => count($rows) <= 25,
	'maxHeight' => '400',
	'cardClass' => 'zone-map',
]) ?>
