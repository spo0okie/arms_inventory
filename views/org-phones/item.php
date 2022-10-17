<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

if (!isset($href)) $href=false;
if (!isset($static_view)) $static_view=true;
if (!isset($show_archived)) $show_archived=true;

if (is_object($model)) { ?>
    <span class="org-phones-item cursor-default <?= $model->archived?'text-muted text-decoration-line-through archived-item':'' ?>" <?= $model->archived&&!$show_archived?'style="display:none"':'' ?> >
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$model->title,
			'static'=>$static_view,
			'modal'=>true,
			'url'=>\yii\helpers\Url::to(['/services/view','id'=>$model->services_id,'showArchived'=>$model->archived]),
		]) ?>
	</span>

<?php } else echo "Отсутствует";