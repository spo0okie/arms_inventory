<?php

use app\components\ItemObjectWidget;

/**
 * Элемент списка DNS-имён: полное имя ссылкой.
 *
 * @var yii\web\View $this
 * @var app\models\DnsNames $model
 * @var bool $static_view
 * @var bool $show_delete
 * @var bool $show_archived
 * @var string $class
 * @var string $suffix
 * @var string $name   подпись вместо полного имени (в карте зоны — имя в зоне без суффикса)
 */

if (empty($model)) return;

if (!isset($name)) $name = $model->fqdn;

$options = [
	'model' => $model,
	'name' => '<span class="text-monospace">' . \yii\helpers\Html::encode($name) . '</span>',
	'nameSuffix' => $suffix ?? '',
	//конвенция item-вью: удаление делается со страницы объекта, а не из чужого списка
	'noDelete' => !($show_delete ?? false),
	'static' => $static_view ?? true,
];
if (isset($class)) $options['item_class'] = $class;
if (isset($show_archived)) $options['show_archived'] = $show_archived;

echo ItemObjectWidget::widget($options);
