<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use app\components\DynaGridWidget;
//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
\app\components\ShowArchivedWidget::$defaultValue=false;
?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'services-comps-index',
		'model' => new \app\models\Comps(),
		'panel' => false,
		'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		'defaultOrder' => ['name','ip','services_ids','comment','os','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'gridOptions' => [
			'pjax' => true,
			'pjaxSettings' => ['options'=>[
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			]],
			'rowOptions'=>function($data){return[
				'class'=>\app\components\ShowArchivedWidget::archivedClass($data),
				'style'=>\app\components\ShowArchivedWidget::archivedDisplay($data),
			];}
		],
	]) ?>
</div>