<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="segments-item <?= $model->code ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['segments/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['segments/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['segments/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>