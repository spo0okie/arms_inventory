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
		<h2>Используется для сервисов/услуг</h2>
		<p>
			<?php
				$render=[];
				foreach ($services as $service) {
					$mode=[];
					if (isset($service['provide'])) $mode[]='Предоставление';
					if (isset($service['support'])) $mode[]='Поддержка';
					
					$render[]=$this->render('/services/item',['model'=>$service['obj'],'static_view'=>$static_view]).' - '.implode(',',$mode);
				}
				echo implode('<br />',$render);
			?>
		</p>
	</div>
<?php } ?>