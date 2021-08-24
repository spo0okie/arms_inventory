<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

if (!isset($static_view)) $static_view=false;
if (!isset($show_delete)) $show_delete=false;


if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="aces-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['aces/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['aces/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="glyphicon glyphicon-pencil"></span>',['aces/update','id'=>$model->id,'return'=>'previous']) ?>
		<?=  $show_delete?Html::a('<span class="glyphicon glyphicon-trash"/>', ['aces/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот элемент? Действие необратимо',
				'method' => 'post',
			],
		]):'' ?>
	</span>
<?php } ?>