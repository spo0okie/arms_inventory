<?php

use app\components\UrlListWidget;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Techs */


?>
<div class="tech-model-view">
	<h3><?= ModelWidget::widget(['model'=>$model->model,'options'=>['long'=>1]]) ?></h3>
	
	<?php if ($model->model->contain_front_rack || $model->model->contain_back_rack) {
		echo ModelWidget::widget(['model'=>$model, 'view'=>'rack/rack', 'options'=>[]]);
	}?>
	
	<?= ModelWidget::widget(['model'=>$model->model->preview,'view'=>'ttip']) ?>
	<h4>Характеристики</h4>
	<p>
		<?= Yii::$app->formatter->asNtext($model->model->comment) ?>
	</p>

	<br />
	
	<?php if ($model->model->individual_specs) { ?>
		<h4>Спецификация:</h4>
		<p><?= Yii::$app->formatter->asNtext($model->specs) ?></p>
		<br />
	<?php } ?>

	<h4>Ссылки:</h4>
	<p>
		<?= UrlListWidget::Widget(['list'=>$model->model->links]) ?>
	</p>
</div>


