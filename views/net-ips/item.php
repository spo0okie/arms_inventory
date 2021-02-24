<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$class='';

if (!empty($model)) {
	if (is_object($model->network)&&is_object($model->network->netVlan)&&is_object($model->network->netVlan->segment)) $class=$model->network->netVlan->segment->code;
	if (!isset($name)) $name=$model->text_addr;
	?>

	<span class="net-ips-item text-monospace <?= $class ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['net-ips/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['net-ips/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['net-ips/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>