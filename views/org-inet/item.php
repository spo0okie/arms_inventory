<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

use app\components\ItemObjectWidget;

if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=true;
if (!isset($name)) $name=$model->name;
if (!isset($icon)) $icon=false;


if (is_object($model)) { ?>
	<?= ItemObjectWidget::widget([
		'model'=>$model,
		'modal'=>true,
		'noDelete'=>true,
		'name'=>$name,
		'namePrefix'=>$icon?'<i class="fas fa-globe"></i>':'',
		'static'=>$static_view,
		'item_class'=>'org-inet-item cursor-default',
		'show_archived'=>$show_archived,
	]) ?>

<?php } else echo "Отсутствует";
