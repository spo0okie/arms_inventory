<?php


/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;
$services=$model->getServicesArr();
$acls=$model->acls;
$jobs=$model->maintenanceJobs;
if (count($services)||count($acls)||count($jobs)) {
?>
	<div class="schedules-services">
		<h3>Используется для</h3>
		<p>
			<?php
			$render=[];
			foreach ($services as $service) {
				$mode=[];
				if (isset($service['provide'])) $mode[]='предоставление';
				if (isset($service['support'])) $mode[]='поддержка';
				
				$render[]=$service['obj']->renderItem($this,['static_view'=>$static_view]).' - '.implode(', ',$mode).' сервиса';
			}
			foreach ($jobs as $job) {
				$render[]=$job->renderItem($this,['static_view'=>$static_view]).' - график выполнения';
			}
			foreach ($acls as $acl) {
				$render[]=$acl->renderItem($this,['static_view'=>$static_view]).' - расписание предоставления доступа';
			}
			echo implode('<br />',$render);
			?>
		</p>
	</div>
<?php } ?>