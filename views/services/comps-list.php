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
foreach ($dataProvider->getModels() as $data) if ($data->ignore_hw==1) {
	$vmCpus+=$data->recursiveServicePartialWeight($model->id)*$data->getCpuCoresCount();
	$vmRam+=$data->recursiveServicePartialWeight($model->id)*$data->getRamGb();
	$vmHdd+=$data->recursiveServicePartialWeight($model->id)*$data->getHddGb();
}

$vCpuTotal=Yii::$app->formatter->asDecimal($vmCpus,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$vmCpus<10?1:0]);
$vRamTotal=Yii::$app->formatter->asDecimal($vmRam,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$vmRam<10?1:0]);
$vHddTotal=Yii::$app->formatter->asDecimal($vmHdd,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$vmHdd<10?1:0]);

if ($model->vm_cores) $vCpuTotal=$vCpuTotal." / ".$model->vm_cores;
if ($model->vm_ram) $vRamTotal=$vRamTotal." / ".$model->vm_ram;
if ($model->vm_hdd) $vHddTotal=$vHddTotal." / ".$model->vm_hdd;

$vmRes=[
	'vCpuCores'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			if (!$data->ignore_hw) return '';
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getCpuCoresCount();
			$total=$data->getCpuCoresCount();
			$partial=Yii::$app->formatter->asDecimal($partial,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$partial<10?1:0]);
			$total=Yii::$app->formatter->asDecimal($total,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$total<10?1:0]);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>$vCpuTotal,
	],
	'vRamGb'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			if (!$data->ignore_hw) return '';
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getRamGb();
			$total=$data->getRamGb();
			$partial=Yii::$app->formatter->asDecimal($partial,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$partial<10?1:0]);
			$total=Yii::$app->formatter->asDecimal($total,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$total<10?1:0]);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>$vRamTotal,
	],
	'vHddGb'=>[
		'value' => function ($data) use ($model){
			/* @var $data \app\models\Comps */
			if (!$data->ignore_hw) return '';
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getHddGb();
			$total=$data->getHddGb();
			$partial=Yii::$app->formatter->asDecimal($partial,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$partial<10?1:0]);
			$total=Yii::$app->formatter->asDecimal($total,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$total<10?1:0]);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>$vHddTotal,
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
		'defaultOrder' => ['name','ip','mac','services_ids','comment','os','vCpuCores','vRamGb','vHddGb','arm_id','places_id','raw_version'],
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