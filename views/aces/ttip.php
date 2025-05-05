<?php

use app\components\IsHistoryObjectWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

?>
<div class="aces-ttip ttip-card">
	<?= IsHistoryObjectWidget::widget(['model'=>$model]) ?>
	<h1>
		<?= Html::encode($model->sname) ?>
	</h1>
	
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
</div>
