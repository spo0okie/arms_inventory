<?php

use app\components\HistoryRecordWidget;
use app\components\UrlListWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
?>
<div class="tech-models-ttip ttip-row">
	<?php if (is_object($model->preview)) { ?>
		<div class="ttip-preview">
			<?= $this->render('/scans/ttip',['model'=>$model->preview]) ?>
		</div>
	<?php } ?>
	<div class="ttip-card">
		
		<?= HistoryRecordWidget::widget(['model'=>$model]) ?>
		<h1><?= Html::a($model->manufacturer->name.' '.$model->name,['/tech-models/view','id'=>$model->id]) ?></h1>
		<?= $this->render('/tech-types/item',['model'=>$model->type]) ?>
		<p>
			<?= Yii::$app->formatter->asNtext($model->comment) ?>
		</p>

		<br />

		<h4>Ссылки:</h4>
		<p>
		<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
		</p>

	</div>
</div>
