<?php

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $static_view bool */
/* @var $show_archived bool|null */

use app\components\ItemObjectWidget;
use app\components\ListObjectsWidget;
use app\components\UpdateObjectWidget;
use app\components\widgets\page\ModelWidget;

if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=null;

//заголовок отвечает «когда/на каком основании» (мы на карточке самого ресурса,
//повторять «Доступ к <ресурс>» смысла нет); карандаш — правка самого ACL
$header=(is_object($model->schedule)?
	ModelWidget::widget(['model'=>$model->schedule, 'options'=>['static_view'=>true]]):
	'Постоянный доступ').':';
if (!$static_view) $header.=' '.UpdateObjectWidget::widget(['model'=>$model]);

//записи доступа: субъекты — типы доступа: пояснение (см. aces/summary.php)
$content='<h5>'.$header.'</h5>'
	.'<div class="px-1">'
	.ListObjectsWidget::widget([
		'models'=>$model->aces,
		'itemViewPath'=>'/aces/summary',
		'item_options'=>['show'=>'subjects','static_view'=>$static_view],
		'show_archived'=>$show_archived,	//иначе внутренний виджет заново возьмет дефолт
		'title'=>false,
		'card'=>false,
		'lineBr'=>false,
		'glue'=>'<br />',
	])
	.'</div>';

//архивность (истекшее расписание, архивный ресурс) виджет возьмет из модели
echo ItemObjectWidget::widget([
	'model'=>$model,
	'link'=>$content,
	'show_archived'=>$show_archived,
]);
