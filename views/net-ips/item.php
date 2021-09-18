<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$class='';

if (!isset($static_view)) $static_view=false;

if (!empty($model)) {
	if (is_object($model->network)) $class=$model->network->segmentCode;
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="net-ips-item <?= $class ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['net-ips/ttip','id'=>$model->id]) ?>"
	><?=
		Html::a($name,['net-ips/view','id'=>$model->id])
	?><?=
		$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['net-ips/update','id'=>$model->id,'return'=>'previous'])
	?></span>
<?php } ?>