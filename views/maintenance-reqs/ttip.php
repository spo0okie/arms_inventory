<?php

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */
/* @var $job app\models\MaintenanceJobs */
/* @var $absorbed app\models\MaintenanceReqs */

?>
<div class="maintenance-reqs-ttip ttip-card">
	<?= $this->render('card',['model'=>$model,'static_view'=>true]) ?>
	<?php if (is_object($job)) { ?>
		<div class="bg-green-striped p-1">
			<h4>Это требование удовлетворено наличием регламентного обслуживания:</h4>
			<?= $this->render('/maintenance-jobs/item',['model'=>$job]) ?>
		</div>
	<?php } ?>
	<?php if (is_object($absorbed)) { ?>
		<div class="bg-red-striped p-1">
			<h4>Это требование избыточно ввиду наличия требования:</h4>
			<?= $this->render('/maintenance-reqs/item',['model'=>$absorbed]) ?>
		</div>
	<?php } ?>
</div>
