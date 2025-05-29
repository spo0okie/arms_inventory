<?php

use app\components\DynaGridWidget;
use app\components\ModelFieldWidget;
use app\components\TabsWidget;
use app\models\Comps;
use app\models\MaintenanceJobs;
use app\models\Services;
use app\models\Techs;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */

Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\MaintenanceJobs::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$this->params['headerContent']=$this->render('header',['model'=>$model]);


$tabs=[];
$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';

DynaGridWidget::handleSave('maintenance-jobs-index');
$tabs[]=[
	'id'=>'children',
	'label'=> 'Состав'
		.'<i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#maintenance-jobs-index-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=>Html::a('Добавить дочернюю операцию',[
			'create','MaintenanceJobs'=>['parent_id'=>$model->id]
		],[
			'class'=>'badge text-bg-success m-0'
		]).<<<HTML
		<div id="mainenanceChildrenTree">
		
			<div class="spinner-border" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$.get("/web/maintenance-jobs/children-tree?id={$model->id}", function(data) {
				    jQuery("#mainenanceChildrenTree").hide().html(data);
				    setTimeout(function (){jQuery("#mainenanceChildrenTree").fadeToggle();ExpandableCardInitAll();},500)
				})
			})
		</script>
HTML,
];

//для корректной работы columns надо чтобы была определена переменная
$dataProvider=new ArrayDataProvider(['allModels'=>$model->services]);
$tabs[]=[
	'id'=>'services',
	'label'=>'Сервисы '.$badge.count($model->services).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'job-services',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/services/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'model' => new Services()
	]),
];

$tabs[]=[
	'id'=>'computers',
	'label'=>'ОС / ВМ '.$badge.count($model->comps).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'job-comps',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->comps]),
		'model' => new Comps()
	]),
];

$tabs[]=[
	'id'=>'techs',
	'label'=>'Оборудование '.$badge.count($model->techs).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'job-techs',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/techs/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->techs]),
		'model' => new Techs()
	]),
];

if (is_object($model->scheduleRecursive)) {
	$tabs[]=[
		'id'=>'schedule',
		'label'=>'Расписание выполнения',
		'content'=>$this->render('/schedules/card',['model'=>$model->scheduleRecursive,'static_view'=>false]),
	];
}


TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'jobs-view-tab-'.$model->id,
];
