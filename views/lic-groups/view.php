<?php


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\models\LicGroups;
use app\models\LicItems;
use app\models\links\LicGroupsInArms;
use app\models\links\LicGroupsInComps;
use app\models\links\LicGroupsInUsers;
use kartik\markdown\Markdown;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

Url::remember();

$this->title = $model->descr;
$static_view=false;
$breadcrumbs=[];
$breadcrumbs[] = ['label' => LicGroups::$titles, 'url' => ['index']];
$breadcrumbs[] = $this->title;

$this->params['breadcrumbs']=$breadcrumbs;

$this->params['headerContent']=
	'<div class="flex-row d-flex flex-nowrap align-content-stretch">'
		.'<div class="me-5 flex-lg-shrink-1">'
			.'<h3>'
				.LinkObjectWidget::widget([
					'model'=>$model,
					'confirmMessage' => 'Действительно удалить этот тип лицензий?',
					'undeletableMessage'=>'Нельзя удалить этот тип лицензий, т.к. есть привязанные к нему объекты',
				])
			.'</h3>'
			.Markdown::convert($model->comment,[])
		.'</div>'
		.'<div class="me-5 flex-lg-grow-0">'
			.ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'soft',
				'show_empty'=>true,
				'glue'=>'<br>',
				'message_on_empty'=>'<div class="alert-striped text-center w-100 p-2">
					<span class="fas fa-exclamation-triangle"></span>
						ОТСУТСТВУЮТ
					<span class="fas fa-exclamation-triangle"></span>
				</div>'
			])
			//.ModelFieldWidget::widget(['model'=>$model,'field'=>'includedBy'])
		.'</div>'
		.'<div class="me-5 flex-lg-shrink-1">'
			.$this->render('/attaches/model-list',compact(['model','static_view']))
		.'</div>'
		.'<div class="flex-fill flex-lg-shrink-0">'
			.'<div class="float-end text-end">'
				.'<small class="opacity-75 ">'.HistoryWidget::widget(['model'=>$model]).'</small>'
				.$this->render('usage',['model'=>$model])
			.'</div>'
		.'</div>'
	.'</div>';

$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';

$tabs=[];

$tabs[]=[
	'id'=>'lic-items',
	'label'=>'Закупки '.$badge.count($model->licItems).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-groups-items',
		//'header' => false,
		'columns' => ArrayHelper::filter(require $_SERVER['DOCUMENT_ROOT'].'/views/lic-items/columns.php',[1,2,3]),
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->licItems]),
		'model' => new LicItems(),
		'createButton' => Html::a('Добавить закупку',['/lic-items/create','LicItems'=>['lic_group_id'=>$model->id]],['class' => 'btn btn-success']),
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
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicGroupsInUsers::findLinks($model->id),'key'=>'id',]),
		'model' => new LicGroupsInUsers()
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
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicGroupsInComps::findLinks($model->id),'key'=>'id',]),
		'model' => new LicGroupsInComps()
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
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicGroupsInArms::findLinks($model->id),'key'=>'id',]),
		'model' => new LicGroupsInArms()
	]),
];



//TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'lic-groups-view-tab-'.$model->id,
];
