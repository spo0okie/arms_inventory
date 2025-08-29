<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\models\LicGroups;
use app\models\LicKeys;
use app\models\links\LicItemsInArms;
use app\models\links\LicItemsInComps;
use app\models\links\LicItemsInUsers;
use kartik\markdown\Markdown;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

Url::remember();
$static_view=false;

if (!isset($keys)) $keys=null;

$contracts=$model->contracts;
$arms=$model->arms;
$deleteable=!count($arms)&&!count($contracts)&&!count($model->keys);

if (!isset($linksData)) $linksData=null;

$this->title = $model->descr;
$this->params['breadcrumbs']=[];
$breadcrumbs=[];
$breadcrumbs[] = ['label' => LicGroups::$titles, 'url' => ['lic-groups/index']];
$breadcrumbs[] = ['label' => $model->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->lic_group_id]];
$breadcrumbs[] = $this->title;

$this->params['breadcrumbs']=$breadcrumbs;

$this->params['headerContent']=
	'<div class="flex-row d-flex flex-nowrap align-content-stretch">'
		.'<div class="me-5 flex-lg-shrink-1">'
			.'<h3>'
				/*.LinkObjectWidget::widget([
					'model'=>$model->licGroup,
					'static' => true,
				]).' / '*/
				.LinkObjectWidget::widget([
					'model'=>$model,
					'hideUndeletable' => false,
				])
			.'</h3>'
			.TextFieldWidget::widget(['model'=>$model,'field'=>'comment'])
			.'<hr/>'
			.ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'contracts',
				'show_empty'=>true,
				'item_options'=>['static_view'=>true],
				'glue'=>'<br>',
				'message_on_empty'=>'<div class="alert-striped text-center w-100 p-2">
								<span class="fas fa-exclamation-triangle"></span>
									ОТСУТСТВУЮТ
								<span class="fas fa-exclamation-triangle"></span>
							</div>'
			])
		.'</div>'
	.'<div class="me-5 flex-lg-shrink-1">'
		.ModelFieldWidget::widget(['model'=>$model,'field'=>'responsible', 'item_options'=>['short'=>true]])
		.ModelFieldWidget::widget(['model'=>$model,'field'=>'support', 'item_options'=>['short'=>true]])
	.'</div>'
	.'<div class="me-5 flex-lg-shrink-1">'
		.ModelFieldWidget::widget(['model'=>$model,'field'=>'serviceRecursive','item_options'=>['static_view'=>true]])
		.$this->render('/attaches/model-list',compact(['model','static_view']))
	.'</div>'
	.'<div class="flex-fill flex-lg-shrink-0">'
		.'<div class="float-end text-end">'
		.'<small class="opacity-75 ">'.HistoryWidget::widget(['model'=>$model]).'</small>'
		.$this->render('stat',['model'=>$model])
		.'</div>'
	.'</div>'
.'</div>';
	
	
	/*
	
	'<div class="row">'.
	'<div class="col-md-9" >'.
		$this->render('hdr',compact(['model','deleteable'])).
	'</div>'.
	'<div class="col-md-3" >'.
		$this->render('stat',['model'=>$model]).
		$this->render('/attaches/model-list',compact(['model','static_view'])).
	'</div>'.
'</div>';*/

$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';

$tabs=[];

$tabs[]=[
	'id'=>'lic-items',
	'label'=>'Ключи '.$badge.count($model->keys).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-groups-items',
		'header' => 'Лицензионные ключи, полученные в этой закупке',
		'columns' => ArrayHelper::filter(require $_SERVER['DOCUMENT_ROOT'].'/views/lic-keys/columns.php',[1,2,3]),
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->keys]),
		'model' => new LicKeys(),
		'createButton' => Html::a('Добавить ключ',
			['/lic-keys/create','LicKeys[lic_items_id]'=>$model->id],
			['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
		),
	]),
];

$tabs[]=[
	'id'=>'users',
	'label'=>'Привязки к пользователям '.$badge.count($model->users).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-groups-users',
		'header' => 'Распределение лицензий по пользователям',
		'columns' => FieldsHelper::addFieldColumns(
			require $_SERVER['DOCUMENT_ROOT'].'/views/lic-links/columns.php',
			'object',
			require $_SERVER['DOCUMENT_ROOT'].'/views/users/columns.php'
		),
		'defaultOrder'=>['object.shortName','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicItemsInUsers::findLinks($model->id),'key'=>'id',]),
		'model' => new LicItemsInUsers()
	]),
];

$tabs[]=[
	'id'=>'computers',
	'label'=>'Привязки к ОС / ВМ '.$badge.count($model->comps).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-groups-comps',
		'header' => 'Распределение лицензий по операционным системам / виртуальным машинам',
		'columns' => FieldsHelper::addFieldColumns(
			require $_SERVER['DOCUMENT_ROOT'].'/views/lic-links/columns.php',
			'object',
			require $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php'
		),
		'defaultOrder'=>['object.name','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicItemsInComps::findLinks($model->id),'key'=>'id',]),
		'model' => new LicItemsInComps()
	]),
];

$tabs[]=[
	'id'=>'techs',
	'label'=>'Привязки к АРМ '.$badge.count($model->arms).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-groups-techs',
		'header' => 'Распределение лицензий по рабочим местам',
		'columns' => FieldsHelper::addFieldColumns(
			require $_SERVER['DOCUMENT_ROOT'].'/views/lic-links/columns.php',
			'object',
			require $_SERVER['DOCUMENT_ROOT'].'/views/techs/columns.php'
		),
		'defaultOrder'=>['object.num','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicItemsInArms::findLinks($model->id),'key'=>'id',]),
		'model' => new LicItemsInArms()
	]),
];

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'lic-groups-view-tab-'.$model->id,
];
