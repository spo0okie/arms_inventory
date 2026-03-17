<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

use app\components\ItemObjectWidget;
use yii\helpers\Url;

if (!isset($href)) $href=false;
if (!isset($static_view)) $static_view=true;
if (!isset($show_archived)) $show_archived=true;
if (!isset($icon)) $icon=false;

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->title,
		'namePrefix'=>$icon?'<i class="fas fa-phone"></i>':'',
		'static'=>$static_view,
		'modal'=>true,
		'url'=> Url::to(['/services/view','id'=>$model->services_id,'showArchived'=>$model->archived]),
		'item_class'=>'org-phones-item cursor-default',
		'show_archived'=>$show_archived,
	]);
} else echo "Отсутствует";
