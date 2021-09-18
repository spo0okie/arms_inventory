<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode($model->name) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['segments/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['segments/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>


<p><?= $model->code ?></p>
<br />

<h4><?= $model->getAttributeLabel('description') ?></h4>
<?= Yii::$app->formatter->asNtext($model->description) ?>

<?php if (!$static_view && strlen($model->history)) { ?>
	<hr/>
	<h4><?= $model->getAttributeLabel('history') ?></h4>
	<p>
		<?= Markdown::convert($model->history) ?>
	</p>
	<br />
<?php } ?>

