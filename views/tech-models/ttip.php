<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

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

		<h1><?= Html::a($model->manufacturer->name.' '.$model->name,['/tech-models/view','id'=>$model->id]) ?></h1>
		<?= $this->render('/tech-types/item',['model'=>$model->type]) ?>
		<p>
			<?= Yii::$app->formatter->asNtext($model->comment) ?>
		</p>

		<br />

		<p>
		<h4>Ссылки:</h4>
		<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
		</p>

	</div>
</div>
