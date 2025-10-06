<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\ModelFieldWidget;
use app\components\TabsWidget;
use app\models\LicGroups;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $licProvider yii\data\ActiveDataProvider */

$this->title = (is_object($model->manufacturer)?$model->manufacturer->name.' ':'').$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$keysCount=0;

foreach ($licProvider->models as $lic) {
	$keysCount+=$lic->activeCount;
}

$nonAgreedClass=($model->isFree||$model->isIgnored)?'':'table-warning';
$scans='';
foreach ($model->scans??[] as $scan) $scans.= $this->render('/scans/thumb',[
	'model'=>$scan,
	'soft_id'=>$model->id,
	'static_view'=>true
]);

$this->params['headerContent']=$this->render('header',['model'=>$model]);

$badge='<span class="badge rounded-pill p-1 m-1 bg-warning">';

$tabs=[];
$tabs[]=[
	'id'=>'hits',
	'label'=>'Обнаружения '.TabsWidget::badgeStart.$dataProvider->count.TabsWidget::badgeEnd,
	'content'=> (
			count($model->compRescans)?
				(\app\components\StripedAlertWidget::widget(['title'=>'Данные устарели, ожидается фоновое сканирование '.count($model->compRescans).' компьютеров']))
			:'')
		.DynaGridWidget::widget([
		'header'=>'Установки',
		'id' => 'soft-comps-list',
		'columns' => array_merge(include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php', [
			'softAgreed'=>[
				'header'=>'В паспорте',
				'value'=>function($comp) use ($model) {
					return array_search($model->id,$comp->soft_ids)?'да':'нет';
				},
				'contentOptions'=>function($comp) use ($model,$nonAgreedClass) {
					return ['class'=>array_search($model->id,$comp->soft_ids)?'table-success':$nonAgreedClass];
				},
			]
		]),
		'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version','softAgreed'],
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		//'panel'=>true
	]),
	'headerOptions'=>['class'=>!$dataProvider->count?'muted-tab':'']
];

$tabs[]=[
	'id'=>'licGroups',
	'label'=>'Совместимые лицензии '.TabsWidget::badgeStart.count($model->licGroups).TabsWidget::badgeEnd,
	'content'=>DynaGridWidget::widget([
		'id' => 'soft-lic-groups-list',
		'header'=>'Типы лицензий',
		'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/lic-groups/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => $licProvider,
		'createButton'=>count($licProvider->models)?('Активных лицензий: '.$keysCount):'',
		'model'=>new LicGroups(),
	]),
	'headerOptions'=>['class'=>!count($model->licGroups)?'muted-tab':''],
];

TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'soft-view-tab-'.$model->id,
];

