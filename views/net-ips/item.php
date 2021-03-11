<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$class='';

if (!empty($model)) {
	if (is_object($model->network)) $class=$model->network->segmentCode;
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="net-ips-item text-monospace <?= $class ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['net-ips/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['net-ips/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['net-ips/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>