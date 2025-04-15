<?php

use app\components\LinkObjectWidget;
use app\components\UrlListWidget;
use kartik\markdown\Markdown;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

if (!isset($static_view)) $static_view=false;
?>

<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model->manufacturer,
		//'name'=>$model->descr,
		'static'=>true,
		//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		'hideUndeletable'=>false
	]) ?>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->descr,
		'static'=>$static_view,
		//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
		'hideUndeletable'=>false
	]) ?>
</h1>

<?= Markdown::convert($model->comment) ?>
<?php if (is_array($model->softLists)&&count($model->softLists)) { ?>
	<ul>
		<?php foreach ($model->softLists as $item) { ?>
			<li><?= Html::a($item->descr,['soft-lists/view','id'=>$item->id]) ?></li>
		<?php } ?>
	</ul>
<?php } else { ?>
	Отсутствуют
<?php } ?>

<?php if ($static_view && is_array($model->scans)&&count($model->scans)) echo $this->render('/scans/thumb',[
	'model'=>$model->scans[0],
	'soft_id'=>$model->id,
	'static_view'=>true
]); ?>

<?php if ($model->links) { ?>
	<h4>Ссылки:</h4>
	<p class="mb-4">
		<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
	</p>
<?php } ?>

<?php if (isset($hitlist) && ($hitlist!=='null')) { ?>
	<h4>Список regexp совпадений:</h4>
	<p class="mb-4">
		<?= Yii::$app->formatter->asNtext($hitlist) ?>
	</p>
<?php } ?>


<h5>Regexp основных элементов ПО</h5>
<p class="mb-4"><?= Yii::$app->formatter->asNtext($model->items) ?></p>

<?php if ($model->additional) { ?>
	<h5>Regexp Дополнительных компонент ПО</h5>
	<p class="mb-4"><?= Yii::$app->formatter->asNtext($model->additional) ?></p>
<?php } ?>
