<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;
$services=$model->getServicesArr();
if (count($services)) {
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
					
					$render[]=$this->render('/services/item',['model'=>$service['obj'],'static_view'=>$static_view]).' - '.implode(', ',$mode);
				}
				echo implode('<br />',$render);
			?>
		</p>
	</div>
<?php } ?>