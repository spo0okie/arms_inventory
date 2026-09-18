<?php

use app\components\ExpandableCardWidget;
use app\components\gridColumns\ItemColumn;
use app\components\widgets\page\ModelWidget;

/**
 * Определение колонок для Grid таблицы DNS-имён (index).
 *
 * @var yii\web\View $this
 * @var app\models\DnsNamesSearch $searchModel
 */

return [
	//полное имя — вычисляемое (host + fqdn зоны), поэтому не сортируется; фильтр — текстом
	'fqdn' => [
		'class' => ItemColumn::class,
		'enableSorting' => false,
	],
	'domain_id',
	//адреса — как у оборудования: карточки IP (с сетью/сегментом по маркеру)
	'ip' => [
		'value' => function ($data) {
			if (!is_object($data)) return null;
			$output = [];
			foreach ($data->netIps as $ip)
				$output[$ip->addr] = ModelWidget::widget(['model' => $ip, 'options' => ['static_view' => true]]);
			return count($output) ? ExpandableCardWidget::widget(['content' => implode('<br />', $output)]) : null;
		},
	],
	'comment',
];
