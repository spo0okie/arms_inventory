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
				
				$render[]=$this->render('/services/item',['model'=>$service['obj'],'static_view'=>$static_view]).' - '.implode(', ',$mode).' сервиса';
			}
			foreach ($jobs as $job) {
				$render[]=$this->render('/maintenance-jobs/item',['model'=>$job,'static_view'=>$static_view]).' - график выполнения';
			}
			foreach ($acls as $acl) {
				$render[]=$this->render('/acls/item',['model'=>$acl,'static_view'=>$static_view]).' - расписание предоставления доступа';
			}
			echo implode('<br />',$render);
			?>
		</p>
	</div>
<?php } ?>