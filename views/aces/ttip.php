<?php

use app\components\HistoryRecordWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

?>
<div class="aces-ttip ttip-card">
	<?= HistoryRecordWidget::widget(['model'=>$model]) ?>
	<h1>
		<?= Html::encode($model->sname) ?>
	</h1>
	
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	<?= $this->render('notepad',['model'=>$model,'static_view'=>true]) ?>
</div>
