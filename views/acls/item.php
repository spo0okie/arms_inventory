<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

if (!isset($static_view)) $static_view=false;


if (!empty($model)) {
	if (($model->comps_id) and is_object($model->comp))
		echo $this->render('/comps/item',['model'=>$model->comp,'static_view'=>true]);
	elseif (($model->techs_id) and is_object($model->tech))
		echo $this->render('/techs/item',['model'=>$model->tech,'static_view'=>true]);
	elseif (($model->services_id) and is_object($model->service))
		echo $this->render('/services/item',['model'=>$model->service,'static_view'=>true]);
	elseif (($model->ips_id) and is_object($model->ip))
		echo $this->render('/ips/item',['model'=>$model->ip,'static_view'=>true]);
	else {
		if (!isset($name)) $name=$model->sname;
	?>
	
		<span class="acls-item"
			  qtip_ajxhrf="<?= \yii\helpers\Url::to(['acls/ttip','id'=>$model->id]) ?>"
		>
			<?=  Html::a($name,['acls/view','id'=>$model->id]) ?>
			<?=  $static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['acls/update','id'=>$model->id,'return'=>'previous']) ?>
		</span>
	<?php }
} ?>