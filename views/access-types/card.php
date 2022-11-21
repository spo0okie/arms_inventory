<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode($model->name) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['access-types/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['access-types/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?= Html::encode($model->comment) ?>

<?php if (strlen($model->notepad)) {
	echo '<p>'.Yii::$app->formatter->asNtext($model->notepad).'</p>';
} ?>

<?php if (count($model->children)) {
	echo '<h4>'.$model->getAttributeLabel('children').'</h4>';
	echo '<ul>';
	foreach ($model->children as $child)
		echo '<li>'.$this->render('item',['model'=>$child,'static_view'=>true]).'</li>';
	echo '</ul>';
} ?>

