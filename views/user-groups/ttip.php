<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;
use app\components\widgets\page\ModelWidget;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

?>
<div class="user-groups-ttip ttip-card">
	<?= ModelWidget::widget(['model'=>$model, 'view'=>'card', 'options'=>['static_view'=>true]]) ?>
</div>
