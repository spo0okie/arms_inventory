<?php

use yii\helpers\Html;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

if (!isset($static_view)) $static_view=false;
?>



<h1>
	<?= $this->render('/manufacturers/item',['model'=>$model->manufacturer]) ?>
	<?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->descr,
		'static'=>$static_view,
		//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		'hideUndeletable'=>false
	]) ?>
</h1>
<p><?= $model->comment?></p>
<br />

<div class="row>">
	<div class="col-lg-6 col-md-12">
		<?php if (isset($hitlist) && ($hitlist!=='null')) { ?>
			<h4>Список regexp совпадений:</h4>
			<p>
				<?= Yii::$app->formatter->asNtext($hitlist) ?>
			</p>
			<br />
		<?php } ?>

		<h4>Основные компоненты входящие в продукт</h4>
		<p><?= Yii::$app->formatter->asNtext($model->items) ?></p>
		<br />

		<h4>Дополнительные компоненты входящие в продукт</h4>
		<p><?= Yii::$app->formatter->asNtext($model->additional) ?></p>
		<br />

		<h4>Членство в списках ПО</h4>
		<p>
			<?php if (is_array($model->softLists)&&count($model->softLists)) foreach ($model->softLists as $item) { ?>
				<?= \yii\helpers\Html::a($item->descr,['soft-lists/view','id'=>$item->id]) ?><br/>
			
			<?php } else { ?>
				Отсутствуют
			<?php } ?>
		</p>
	</div>
	<div class="col-lg-6 col-md-12">
		<?= Markdown::convert($model->comment) ?>
	</div>
</div>
