<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

if (!empty($model)) {
	if (!isset($name)) $name=$model->descr;
	?>

	<span class="soft-lists-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['soft-lists/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['soft-lists/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['soft-lists/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>