<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */


?>
<div class="tech-model-view">
	<h3><?= $this->render('/tech-models/item',['model'=>$model->model,'long'=>1]) ?></h3>
	<?= $this->render('/scans/ttip',['model'=>$model->model->preview]) ?>
	<h4>Характеристики</h4>
	<p>
		<?= Yii::$app->formatter->asNtext($model->model->comment) ?>
	</p>

	<br />
	
	<?php if ($model->model->individual_specs) { ?>
		<h4>Спецификация:</h4>
		<p><?= \Yii::$app->formatter->asNtext($model->specs) ?></p>
		<br />
	<?php } ?>

	<h4>Ссылки:</h4>
	<p>
		<?= \app\components\UrlListWidget::Widget(['list'=>$model->model->links]) ?>
	</p>
</div>
