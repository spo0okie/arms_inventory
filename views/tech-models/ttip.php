<?php

use app\components\IsHistoryObjectWidget;
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
		
		<?= IsHistoryObjectWidget::widget(['model'=>$model]) ?>
		<h1><?= Html::a($model->manufacturer->name.' '.$model->name,['/tech-models/view','id'=>$model->id]) ?></h1>
		<?= $model->type->renderItem($this) ?>
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
