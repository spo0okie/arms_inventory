<?php

use app\components\ModelFieldWidget;
use app\components\TableTreePrefixWidget;
use app\components\TextFieldWidget;
use app\components\UrlListWidget;
use app\models\Services;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Services[] */

//if (!isset($columns)) $columns=['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'];

$renderer=$this;

//Суммы
$totalSites=[];
$totalSegments=[];
$totalSupport=[];
$totalComps=[];
$totalTechs=[];

foreach ($dataProvider->models as $service) {
	/* @var $service Services */
	
	if (is_object($service->segmentRecursive)) {
		$totalSegments[$service->segmentRecursive->id]=$service->segmentRecursive;
	}
	
	if (is_object($service->responsibleRecursive)) if (!isset($totalSupport[$service->responsibleRecursive->id]))
		$totalSupport[$service->responsibleRecursive->id]=$renderer->render('/users/item', ['model' => $service->responsibleRecursive,'short'=>true]);

	if (is_array($service->supportRecursive))
		foreach ($service->supportRecursive as $user) if (!isset($totalSupport[$user->id]))
			$totalSupport[$user->id]=$renderer->render('/users/item', ['model' => $user,'short'=>true]);
	
	if (is_array($service->comps))
		foreach ($service->comps as $comp) if (!isset($totalComps[$comp->id]))
			$totalComps[$comp->id]=$renderer->render('/comps/item', ['model' => $comp,'short'=>true]);

	if (is_array($service->techs))
		foreach ($service->techs as $tech) if (!isset($totalTechs[$tech->id]))
		$totalTechs[$tech->id]=$renderer->render('/techs/item', ['model' => $tech,'short'=>true]);
}

$totalSupportRendered=implode(', ',$totalSupport);
if (count($totalSupport)>10)
	$totalSupportRendered='<span onclick="{$(this).hide();$(\'#segmentsSupportTotal\').show()}">Показать '.count($totalSupport).' человек</span>'.
		'<span id="segmentsSupportTotal" style="display: none">'.$totalSupportRendered.'</span>';

$totalCompsAndTechsRendered=implode(', ',array_merge($totalComps,$totalTechs));
if ((count($totalComps)+count($totalTechs))>10)
	$totalCompsAndTechsRendered='<span onclick="$(this).hide();$(\'#segmentsCompsAndTechsTotal\').show()">Показать '.(count($totalComps)+count($totalTechs)).' элементов</span>'.
		'<span id="segmentsCompsAndTechsTotal" style="display: none">'.$totalCompsAndTechsRendered.'</span>';



//формируем список столбцов для рендера
return [
	'name' => [
		'value' => function ($data) {
			$name=$this->render('/services/item', ['model' => $data,'noDelete'=>true,'icon'=>true]);
			if ($depth=$data->treeDepth) {
				return TableTreePrefixWidget::widget(['prefix'=>$data->treePrefix]).$name.'</span>';
			}
			return $name;
		},
		'contentOptions'=>function($data){return ['class'=>'name_col '.($data->treeDepth?
			'tree-col p-0 overflow-hidden position-relative'
			:''
		) ];}
	],
	'segment' => [
		'value' => function ($data) {return $this->render('/segments/item', ['model' => $data->segmentRecursive]);},
	],
	'description' => [
		'value' => function ($data) {
			return \app\helpers\ArrayHelper::implode('<br>',[
				TextFieldWidget::widget(['model'=>$data,'field'=>'description']),
				UrlListWidget::Widget(['list'=>$data->links])
			]);
		},
	],
	'responsible' => [
		//'header' => 'Отв., поддержка.',
		'value' => function ($data) {
			/** @var $data Services */
			$output = [];
			if (is_object($data->responsibleRecursive))
				$output[] = '<div class="pe-2"><strong>'.$this->render('/users/item', ['model' => $data->responsibleRecursive,'short'=>true]).'</strong></div>';
			
			if (is_object($data->infrastructureResponsibleRecursive))
				$output[] = '<div class="pe-2"><strong>'.$this->render('/users/item', ['model' => $data->infrastructureResponsibleRecursive,'short'=>true]).'</strong></div>';
			
			if (is_array($data->supportRecursive)) foreach ($data->supportRecursive as $user)
				$output[] = '<div class="pe-2">'.$this->render('/users/item', ['model' => $user,'short'=>true]).'</div>';
			
			if (is_array($data->infrastructureSupportRecursive)) foreach ($data->infrastructureSupportRecursive as $user)
				$output[] = '<div class="pe-2">'.$this->render('/users/item', ['model' => $user,'short'=>true]).'</div>';
			
			return '<div class="d-flex flex-wrap">'. implode(' ', $output).'</div>';
		},
		'footer'=>$totalSupportRendered,
	],
	'arms' => [
		'value' => function ($data) {
			$output = [];
			if (is_array($data->arms)) foreach ($data->arms as $arm)
				$output[] = '<div class="pe-2">'.$this->render('/techs/item', ['model' => $arm,'static_view'=>true]).'</div>';
			return '<div class="d-flex flex-wrap">'. implode(' ', $output).'</div>';
		},
	],
	'comps' =>[
		'value' => function ($data) {
			$output = [];
			if (is_array($data->comps)) foreach ($data->comps as $comp)
				$output[] = '<div class="pe-2">'.$this->render('/comps/item', ['model' => $comp,'static_view'=>true]).'</div>';
			return '<div class="d-flex flex-wrap">'. implode(' ', $output).'</div>';
		},
	],
	'techs'=>[
		'value' => function ($data) {
			$output = [];
			if (is_array($data->techs)) foreach ($data->techs as $tech)
				$output[] = '<div class="pe-2">'.$this->render('/techs/item', ['model' => $tech,'static_view'=>true]).'</div>';
			return '<div class="d-flex flex-wrap">'. implode(' ', $output).'</div>';
		},
	],
	'compsAndTechs'=> [
		'value' => function ($data) {
			$output = [];
			if (is_array($data->comps)) foreach ($data->comps as $comp)
				$output[] = '<div class="pe-2">'.$this->render('/comps/item', ['model' => $comp,'static_view'=>true]).'</div>';
			if (is_array($data->techs)) foreach ($data->techs as $tech)
				$output[] = '<div class="pe-2">'.$this->render('/techs/item', ['model' => $tech,'static_view'=>true]).'</div>';
			return '<div class="d-flex flex-wrap">'.implode(' ', $output).'</div>';
		},
		'footer' => $totalCompsAndTechsRendered,
		//'contentOptions'=>['class'=>'d-flex flex-row']
	],
	/*'places' => [
		'value' => function ($data) {
			$output = [];
			if (is_array($data->places)) foreach ($data->places as $place)
				$output[] = $this->render('/places/item', ['model' => $place,'short'=>true,'static_view'=>true]);
			return count($output) ? implode(', ', $output) : null;
		},
	],*/
	'sites'=> [
		'value' => function ($data) use ($renderer) {
			$output = [];
			if (is_array($data->sitesRecursive)) foreach ($data->sitesRecursive as $site)
				$output[] = '<div class="pe-2">'.$renderer->render('/places/item', ['model' => $site,'short'=>true]).'</div>';
			return '<div class="d-flex flex-wrap">'.implode(' ', $output).'</div>';
		},
	],
	'providingSchedule' => [
		'value' => function ($data) {return $this->render('/schedules/item',['model'=>$data->providingScheduleRecursive,'static_view'=>true,'empty'=>'']);},
	],
	'supportSchedule' => [
		'value' => function ($data) {return $this->render('/schedules/item',['model'=>$data->supportScheduleRecursive,'static_view'=>true,'empty'=>'']);},
	],
	'maintenanceReqs' => [
		'value' => function ($data) {
			/** @var $data Services */
			return ModelFieldWidget::widget([
				'model'=>$data,
				'field'=>'maintenanceReqsRecursive',
				'title'=>false,
				'card_options'=>['cardClass'=>'m-0']
			]);
		},
	],
	'maintenanceJobs' => [
		'value' => function ($data) {return ModelFieldWidget::widget(['model'=>$data,'field'=>'maintenanceJobs','title'=>false,'item_options'=>['static_view'=>true]]);},
	],
	'weight',
];
