<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \app\models\Services */

use app\components\DynaGridWidget;
//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
\app\components\ShowArchivedWidget::$defaultValue=false;


$vmCpus=0;
$vmRam=0;
$vmHdd=0;
foreach ($dataProvider->getModels() as $data) {
	$vmCpus+=$data->recursiveServicePartialWeight($model->id)*$data->getCpuCoresCount();
	$vmRam+=$data->recursiveServicePartialWeight($model->id)*$data->getRamGb();
	$vmHdd+=$data->recursiveServicePartialWeight($model->id)*$data->getHddGb();
}

$vmRes=[
	'cpuCores'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getCpuCoresCount();
			$total=$data->getCpuCoresCount();
			$partial=Yii::$app->formatter->asDecimal($partial);
			$total=Yii::$app->formatter->asDecimal($total);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>Yii::$app->formatter->asDecimal($vmCpus),
	],
	'ramGb'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getRamGb();
			$total=$data->getRamGb();
			$partial=Yii::$app->formatter->asDecimal($partial);
			$total=Yii::$app->formatter->asDecimal($total);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>Yii::$app->formatter->asDecimal($vmRam),
	],
	'hddGb'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getHddGb();
			$total=$data->getHddGb();
			$partial=Yii::$app->formatter->asDecimal($partial);
			$total=Yii::$app->formatter->asDecimal($total);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>Yii::$app->formatter->asDecimal($vmHdd),
	],
];
?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'services-comps-index',
		'model' => new \app\models\Comps(),
		'panel' => false,
		'columns' => array_merge(
			include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
			$vmRes
		),
		'defaultOrder' => ['name','ip','mac','services_ids','comment','os','cpuCores','ramGb','hddGb','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'gridOptions' => [
			'showFooter' => true,
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