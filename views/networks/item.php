<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="networks-item text-monospace"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['networks/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['networks/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['networks/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>