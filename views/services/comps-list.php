<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\DynaGridWidget;

?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'services-comps-index',
		'model' => new \app\models\Comps(),
		'panel' => false,
		'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		'defaultOrder' => ['name','ip','services_ids','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'gridOptions' => [
			'pjax' => true,
			'pjaxSettings' => ['options'=>[
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			]],
		],
	]) ?>
</div>