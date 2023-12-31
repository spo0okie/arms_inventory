<?php

use app\components\ListObjectsWidget;

/* @var $this yii\web\View */
/* @var $models app\models\Acls[] */

echo ListObjectsWidget::widget([
	'models' => $models,
	'itemViewPath'=>'/acls/list-item',
	'title' => 'Предоставлен доступ',
//	'item_options' => ['static_view' => $static_view],
	'card_options' => ['cardClass' => 'mb-3'],
]);

