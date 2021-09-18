<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="net-vlans-item text-monospace <?= $model->domainCode ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['net-vlans/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['net-vlans/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="fas fa-pencil-alt"></span>',['net-vlans/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>