<?php

use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
?>
<div class="users-ttip ttip-card">
	<?= ModelWidget::widget(['model'=>$model, 'view'=>'card', 'options'=>['static_view'=>true]]) ?>
</div>