<?php

use app\components\DynaGridWidget;
use app\models\NetVlans;
use app\models\NetVlansSearch;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\NetDomains $model */

$searchModel = new NetVlansSearch();
$dataProvider = $searchModel->searchDomain((int)$model->id, Yii::$app->request->queryParams);
$columns = require Yii::getAlias('@app/views/net-vlans/columns.php');

// Домен уже задан карточкой
unset($columns['domain_id']);
$renderName = $columns['name']['value'];
// Для свободной строки открываем создание, сохраняя стандартный рендер существующего VLAN.
$createUrl = static fn(NetVlans $vlan) => [
	'/net-vlans/create', 'NetVlans' => ['vlan' => $vlan->vlan, 'domain_id' => $model->id],
];
$columns['name']['value'] = static fn(NetVlans $vlan) => $vlan->isNewRecord
	? Html::a(Html::encode($vlan->nameRecursive ?: 'Создать VLAN'), $createUrl($vlan))
	: $renderName($vlan);

// Дополняем общие колонки только поведением, специфичным для списка номеров домена.
$columns['vlan']['filterInputOptions'] = [
	'class' => 'form-control', 'placeholder' => 'Номер или диапазон', 'aria-label' => 'Номер VLAN',
];
$columns['vlan']['value'] = static fn(NetVlans $vlan) => Html::a((string)$vlan->vlan,
	$vlan->isNewRecord ? $createUrl($vlan) : ['/net-vlans/view', 'id' => $vlan->id]);
$columns['availability'] = [
	'filter' => ['used' => 'Занятые', 'used_reserved' => 'Занятые + резервы', 'all' => 'Все', 'free' => 'Свободные (включая резервы)'],
	'filterInputOptions' => ['class' => 'form-control', 'prompt' => null, 'aria-label' => 'Статус VLAN'],
	'value' => static fn(NetVlans $vlan) => !$vlan->isNewRecord
		? 'Занят' : ($vlan->parent !== null ? 'Зарезервирован' : 'Свободен'),
];

echo Html::errorSummary($searchModel, ['class' => 'alert alert-danger']);
echo DynaGridWidget::widget([
	'id' => 'domain-vlans-grid',
	'header' => 'VLAN',
	'createButton' => Html::a('Новый', ['/net-vlans/create', 'NetVlans' => ['domain_id' => $model->id]], ['class' => 'btn btn-success']),
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'defaultOrder' => ['vlan', 'availability', 'name', 'comment'],
	'columns' => $columns,
	'gridOptions' => [
		// На карточке фильтры обновляют страницу целиком вместе с состоянием URL.
		'pjax' => false,
		'filterUrl' => ['/net-domains/view', 'id' => $model->id],
		'rowOptions' => static fn(NetVlans $vlan) => [
			'class' => $vlan->isNewRecord && $vlan->parent !== null ? 'opacity-50' : '',
		],
	],
]);
