<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (!isset($static_view)) $static_view=false;
if (!isset($show_archived)) $show_archived=true;
if (!isset($name)) $name=$model->name;


if (is_object($model)) { ?>
	<span class="org-inet-item cursor-default <?= $model->archived?'text-muted text-decoration-line-through archived-item':'' ?>" <?= $model->archived&&!$show_archived?'style="display:none"':'' ?> >
    <?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'modal'=>true,
		'noDelete'=>true,
		'name'=>$name
	]) ?>
	</span>

<?php } else echo "Отсутствует";