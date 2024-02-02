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
					'confirmMessage' => 'Действительно удалить эти регламентные операции?',
					'undeletableMessage'=>'Нельзя удалить эту схему обслуживания, т.к. есть привязанные к ней объекты',
				])
			.'</h1>'
			.Yii::$app->formatter->asNtext($model->description)
		.'</div>'
		.'<div class="me-5">'
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'service'])
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'responsible'])
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'support'])
		.'</div>'
		.'<div class="me-5">'
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'schedule'])
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'reqs'])
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
