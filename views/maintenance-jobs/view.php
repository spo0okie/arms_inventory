<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\WikiPageWidget;
use app\models\Comps;
use app\models\Services;
use app\models\Techs;
use yii\data\ArrayDataProvider;
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


$dataProvider=new ArrayDataProvider(['allModels'=>$model->services]);
$tabs[]=[
	'id'=>'services',
	'label'=>'Сервисы '.$badge.count($model->services).'</span>',
	'content'=>'<div class="container-fluid">'.DynaGridWidget::widget([
		'id' => 'job-services',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/services/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => $dataProvider,
		'model' => new Services()
	]).'</div>',
];

$tabs[]=[
	'id'=>'computers',
	'label'=>'ОС / ВМ '.$badge.count($model->comps).'</span>',
	'content'=>'<div class="container-fluid">'.DynaGridWidget::widget([
		'id' => 'job-comps',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->comps]),
		'model' => new Comps()
	]).'</div>',
];

$tabs[]=[
	'id'=>'techs',
	'label'=>'Оборудование '.$badge.count($model->techs).'</span>',
	'content'=>'<div class="container-fluid">'.DynaGridWidget::widget([
		'id' => 'job-techs',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/techs/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->techs]),
		'model' => new Techs()
	]).'</div>',
];

if (is_object($model->schedule)) {
	$tabs[]=[
		'id'=>'schedule',
		'label'=>'Расписание выполнения',
		'content'=>'<div class="container-fluid">'.$this->render('/schedules/card',['model'=>$model->schedule,'static_view'=>false]).'</div>',
	];
	
}


$tabNumber=0;
$wikiLinks= WikiPageWidget::getLinks($model->links);
foreach ($wikiLinks as $name=>$url) {
	$tabs[]=[
		'id'=>'wiki'.$tabNumber,
		'label'=>($name==$url)?'Wiki':$name,
		'content'=> WikiPageWidget::Widget(['list'=>$model->links,'item'=>$name]),
	];
	$tabNumber++;
}

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=['cookieName'=>'jobs-view-tab-'.$model->id];
