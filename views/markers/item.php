<?php
/** Маркер: элемент показывает сам себя в своих цветах */

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\models\Markers;

/* @var $model Markers */

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model' => $model,
		'marker' => $model,
		'link' => LinkObjectWidget::widget([
			'model' => $model,
			'static' => $static_view ?? true,
			'noDelete' => $noDelete ?? true,
			'hideUndeletable' => $hideUndeletable ?? true,
		]),
	]);
}
