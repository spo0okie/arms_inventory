<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= $this->render('/techs/item', ['model'=>$model->tech,'static_view'=>true]) ?>
	<?= \app\models\Ports::$tech_postfix.\app\models\Ports::$port_prefix.Html::encode($model->name) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['ports/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['ports/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?php if (!empty($model->comment)) {
	echo Yii::$app->formatter->asNtext($model->comment).'<br />';
} ?>

<?php
if (is_object($model->linkPort)||is_object($model->linkTech)||is_object($model->linkArm))
	echo '<h4><span class="glyphicon glyphicon-sort"></span></h4>';

if (is_object($model->linkPort)) {
	echo $this->render('/ports/item',['model'=>$model->linkPort,'static_view'=>$static_view,'include_tech'=>true]);
} elseif (is_object($model->linkTech)) {
	echo $this->render('/techs/item',['model'=>$model->linkTech,'static_view'=>$static_view]);
} elseif (is_object($model->linkArm)) {
	echo $this->render('/arms/item',['model'=>$model->linkArm,'static_view'=>$static_view]);
}


