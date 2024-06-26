<?php

use app\components\UpdateObjectWidget;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

if (!isset($static_view)) $static_view=false;
if (!isset($modal)) $modal=false;


if (!empty($model)) {
	$update='';
	if (!$static_view) {
		$update=UpdateObjectWidget::widget([
			'model'=>$model,
			'modal'=>$modal,
		]);
	}
	if (($model->comps_id) and is_object($model->comp))
		echo $this->render('/comps/item',['model'=>$model->comp,'static_view'=>true]).$update;
	elseif (($model->techs_id) and is_object($model->tech))
		echo $this->render('/techs/item',['model'=>$model->tech,'static_view'=>true]).$update;
	elseif (($model->services_id) and is_object($model->service))
		echo $this->render('/services/item',['model'=>$model->service,'static_view'=>true]).$update;
	elseif (($model->ips_id) and is_object($model->ip))
		echo $this->render('/net-ips/item',['model'=>$model->ip,'static_view'=>true]).$update;
	elseif (($model->networks_id) and is_object($model->network))
		echo $this->render('/networks/item',['model'=>$model->network,'static_view'=>true]).$update;
	else {
		if (!isset($name)) $name=$model->sname;
	?>
	
		<span class="acls-item"
			  qtip_ajxhrf="<?= Url::to(['acls/ttip','id'=>$model->id]) ?>"
		>
			<?=  Html::a($name,['acls/view','id'=>$model->id]).$update; ?>
		</span>
	<?php }
} ?>