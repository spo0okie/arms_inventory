<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model Services */

use app\components\DynaGridWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\models\Comps;
use app\models\Services;
use app\models\Techs;
use kartik\editable\Editable;

//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
ShowArchivedWidget::$defaultValue=false;
$static_view=false;
$compColumns=include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php';
$techsColumns=include $_SERVER['DOCUMENT_ROOT'].'/views/techs/columns.php';

$vmCpus=0;
$vmRam=0;
$vmHdd=0;
foreach ($dataProvider->getModels() as $data) if (isset($data->ignore_hw) && $data->ignore_hw==1 && !$data->archived) {
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
			/* @var $data Comps */
			if (get_class($data)!= Comps::class) return '';
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
			/* @var $data Comps */
			if (get_class($data)!= Comps::class) return '';
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
			/* @var $data Comps */
			if (get_class($data)!= Comps::class) return '';
			if (!$data->ignore_hw) return '';
			$partial=$data->recursiveServicePartialWeight($model->id)*$data->getHddGb();
			$total=$data->getHddGb();
			$partial=Yii::$app->formatter->asDecimal($partial,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$partial<10?1:0]);
			$total=Yii::$app->formatter->asDecimal($total,null,[NumberFormatter::MAX_FRACTION_DIGITS=>$total<10?1:0]);
			return ($total==$partial)?$total:"$partial / $total";
		},
		'footer'=>$vHddTotal,
	],
	'name'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['name']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['model']['value']($data);
			return 'Class error: '.get_class($data);
		},
		'contentOptions'=>function ($data) use ($compColumns) {
			if (get_class($data)== Comps::class) return $compColumns['name']['contentOptions']($data);
			return [];
		}
	],
	'ip'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['ip']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['ip']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],
	'mac'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['mac']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['mac']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],
	'services_ids'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['services_ids']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['services_ids']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],
	'comment'=>[
		'class'=>'kartik\grid\EditableColumn',
		'editableOptions'=> function ($model) {
			$field='error';
			$class='error';
			if (get_class($model)== Comps::class) {$class='comps';$field='comment';}
			if (get_class($model)== Techs::class) {$class='techs';$field='history';}
		return [
			'name'=>'comment',
			'header'=>'Комментарий',
			'format'=>Editable::FORMAT_LINK,
			'inputType' => Editable::INPUT_TEXT,
			'inlineSettings' => [
				'templateBefore'=>'<div class="kv-editable-form-inline d-flex w-100 g-0 m-0"><div class="mb-2">{loading}</div>',
			],
			'asPopover' => false,
			'value' => $model[$field],
			'buttonsTemplate'=>'{submit}',
			'options' => [
				'class' => 'w-100',
				'placeholder'=>'Введите комментарий...',
			],
			'containerOptions'=>['class'=>'w-100 p-0 m-0'],
			'contentOptions'=>['class'=>'p-0 m-0'],
			'inputFieldConfig'=>['options'=>['class'=>'flex-grow-1']],
			'editableValueOptions'=>['class'=>'p-0 m-0 border-0 text-start bg-transparent',],
			'formOptions' => [
				'action' => [
					'/'.$class.'/editable',
				]
			],
		];},
	],
	//'comment',
	'os'=>array_merge($compColumns['os'],[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['os']['value']($data);
			return '';
		}
	]),
	'arm_id'=>[
		'label'=>'Инв. номер',
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['arm_id']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['num']['value']($data);
			return '';
		}
	],
	'places_id'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['places_id']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['place']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],
	'maintenanceJobs' => [
		'value' => function ($data) {return ModelFieldWidget::widget(['model'=>$data,'field'=>'maintenanceJobs','title'=>false,'item_options'=>['static_view'=>true]]);},
	],
	'effectiveMaintenanceReqs'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['effectiveMaintenanceReqs']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['effectiveMaintenanceReqs']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],
	'lics'=>[
		'value' => function ($data) use ($compColumns,$techsColumns) {
			if (get_class($data)== Comps::class) return $compColumns['lics']['value']($data);
			if (get_class($data)== Techs::class) return $techsColumns['lics']['value']($data);
			return 'Class error: '.get_class($data);
		}
	],

];

?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'services-comps-index',
		'pageUrl'=>['/services/view','id'=>$model->id],
		'model' => new Comps(),
		'panel' => false,
		'columns' => array_merge(
			//include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
			$vmRes
		),
		'defaultOrder' => ['name','ip','mac','services_ids','comment','os','vCpuCores','vRamGb','vHddGb','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'toggleButtonGrid'=>[
			'label' => '<i class="fas fa-wrench fa-fw"></i>',
			'title' => 'Персонализировать настройки таблицы',
			'data-pjax' => false,
			'class' => 'd-none',
		],
		'gridOptions' => [
			'layout'=>'{dynagrid}{items}<div class="servicesCompsIndexExport">{export}</div>',
			'showFooter' => true,
			'pjax' => true,
			'pjaxSettings' => ['options'=>[
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			]],
			'rowOptions'=>function($data){return[
				'class'=> ShowArchivedWidget::archivedClass($data),
				'style'=> ShowArchivedWidget::archivedDisplay($data),
			];}
		],
	]) ?>
</div>