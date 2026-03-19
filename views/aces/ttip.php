<?php

use app\components\IsHistoryObjectWidget;
use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

?>
<div class="aces-ttip ttip-card">
	<?= IsHistoryObjectWidget::widget(['model'=>$model]) ?>
	<h1>
		<?= Html::encode($model->sname) ?>
	</h1>
	
	<?= ModelWidget::widget(['model'=>$model,'static_view'=>true,'view'=>'card']) ?>
</div>
