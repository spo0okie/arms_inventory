<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="segments-item <?= $model->code ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['segments/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['segments/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['segments/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>