<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

if (!isset($columns)) $columns=['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'];

$renderer=$this;

//Суммы
$totalSites=[];
$totalSegments=[];
$totalSupport=[];
$totalComps=[];
$totalTechs=[];

foreach ($dataProvider->models as $model) {
	/* @var $model \app\models\Services */
	
	if (is_object($model->segmentRecursive)) {
		$totalSegments[$model->segmentRecursive->id]=$model->segmentRecursive;
	}
	
	if (is_object($model->responsibleRecursive)) if (!isset($totalSupport[$model->responsibleRecursive->id]))
		$totalSupport[$model->responsibleRecursive->id]=$renderer->render('/users/item', ['model' => $model->responsibleRecursive,'short'=>true]);

	if (is_array($model->supportRecursive))
		foreach ($model->supportRecursive as $user) if (!isset($totalSupport[$user->id]))
			$totalSupport[$user->id]=$renderer->render('/users/item', ['model' => $user,'short'=>true]);;
	
	if (is_array($model->comps))
		foreach ($model->comps as $comp) if (!isset($totalComps[$comp->id]))
			$totalComps[$comp->id]=$renderer->render('/comps/item', ['model' => $comp,'short'=>true]);

	if (is_array($model->techs))
		foreach ($model->techs as $tech) if (!isset($totalTechs[$tech->id]))
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
		'value' => function ($data) {return $this->render('/services/item', ['model' => $data,'crop_site'=>true]);},
	],
	'segment' => [
		'value' => function ($data) {return $this->render('/segments/item', ['model' => $data->segmentRecursive]);},
	],
	'description' => [
		'value' => function ($data) {
			return \app\components\UrlListWidget::Widget(['list'=>$data->links]).' '.$data->description;
		},
	],
	'responsible' => [
		//'header' => 'Отв., поддержка.',
		'value' => function ($data) {
			$output = [];
			if (is_object($data->responsibleRecursive))
				$output[] = '<div class="pe-2"><strong>'.$this->render('/users/item', ['model' => $data->responsibleRecursive,'short'=>true]).'</strong></div>';
			if (is_array($data->supportRecursive)) foreach ($data->supportRecursive as $user)
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
		'label' => 'Серв./Оборуд.',
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
	]

];
