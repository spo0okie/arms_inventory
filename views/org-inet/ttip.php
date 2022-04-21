<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

$static_view=true;

?>
<div class="org-inet-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	<hr />
	<h4>Услуга связи</h4>
	<p>
		<?= $this->render('/services/item',['model'=>$model->service ,'static_view'=>$static_view]) ?>
	</p>


	<h4>Провайдер</h4>
	<?= is_object($model->partner)?$this->render('/partners/card',['model'=>$model->partner,'static_view'=>$static_view]):'' ?>

</div>
