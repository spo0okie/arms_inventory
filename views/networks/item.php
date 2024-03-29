<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
if (!isset($static_view)) $static_view=false;
if (!isset($icon)) $icon=false;
if (!isset($class)) $class='text-monospace';

if (is_object($model)) {
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="object-item networks-item <?= $class ?> <?= $model->segmentCode ?>"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['networks/ttip','id'=>$model->id]) ?>"
	>
		<?=
		Html::a(($icon?'<span class="fas fa-network-wired"></span>':'').$name,['networks/view','id'=>$model->id])
		?><?=
		$static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['networks/update','id'=>$model->id,'return'=>'previous'])
		?>
	</span>
<?php } ?>