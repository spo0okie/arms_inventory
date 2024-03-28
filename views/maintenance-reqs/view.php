<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\TabsWidget;
use app\models\Comps;
use app\models\Services;
use app\models\Techs;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */

Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\MaintenanceReqs::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$this->params['headerContent']=
	'<div class="float-end text-end">'
		.'<small class="opacity-75">'.HistoryWidget::widget(['model'=>$model]).'</small>'
		.'<br>'
		.ShowArchivedWidget::widget()
	.'</div>'
	
	.'<div class="flex-row d-flex">'
		.'<div class="me-5">'
			.'<h1>'
				.LinkObjectWidget::widget([
					'model'=>$model,
					'confirmMessage' => 'Действительно удалить эти требования?',
					'undeletableMessage'=>'Нельзя удалить эту требования обслуживания, т.к. есть привязанные к ним объекты',
				])
			.'</h1>'
			.Yii::$app->formatter->asNtext($model->description)
		.'</div>'
		.'<div class="me-5">'
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'includes'])
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'includedBy'])
		.'</div>'
		.'<div class="me-5">'
			.($model->is_backup?'<i class="fas fa-archive"></i> Резервное копирование<br>':'')
			.($model->spread_comps?'<i class="fas fa-laptop-code"></i> Распространяется на ОС/ВМ<br>':'')
			.($model->spread_techs?'<i class="fas fa-print"></i> Распространяется на оборудование<br>':'')
		.'</div>'
		.'<div class="flex-fill">'
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'links'])
			.$this->render('/attaches/model-list',['model'=>$model,'static_view'=>false])
		.'</div>'
	.'</div>'
;


$tabs=[];
$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';


$dataProvider=new ArrayDataProvider(['allModels'=>$model->services]);
$tabs[]=[
	'id'=>'services',
	'label'=>'Сервисы '.$badge.count($model->services).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'reqs-services',
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
		'id' => 'reqs-comps',
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
		'id' => 'reqs-techs',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/techs/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->techs]),
		'model' => new Techs()
	]),
];


TabsWidget::addWikiLinks($tabs,$model->links);

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'reqs-view-tab-'.$model->id
];
