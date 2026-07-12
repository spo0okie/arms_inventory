<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\helpers\FieldsHelper;
use app\models\LicGroups;
use app\models\links\LicKeysInArms;
use app\models\links\LicKeysInComps;
use app\models\links\LicKeysInUsers;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */

Url::remember();

$this->title = $model->keyShort;
$this->params['breadcrumbs'][] = ['label' => LicGroups::$titles, 'url' => ['lic-groups/index']];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->licItem->lic_group_id]];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->descr, 'url' => ['lic-items/view','id'=>$model->lic_items_id]];
$this->params['breadcrumbs'][] = $this->title;

$this->params['headerContent']=
	'<div class="flex-row d-flex flex-nowrap align-content-stretch">'
		.'<div class="me-5 flex-lg-shrink-1">'
			.'<h3>'
				/*.LinkObjectWidget::widget([
					'model'=>$model->licItem->licGroup,
					'static' => true,
				]).' / '
				.LinkObjectWidget::widget([
					'model'=>$model->licItem,
					'static' => true,
				]).' / '*/
				.LinkObjectWidget::widget([
					'model'=>$model,
					'hideUndeletable' => false,
				])
			.'</h3>'
			.ModelFieldWidget::renderFieldTitle($model,'key_text',null,'h4')
			.'<pre>'.Html::encode($model->key_text).'</pre>'
			.'<hr>'
			.ModelFieldWidget::renderFieldRow($model,'comment')
		.'</div>'
	//.'<div class="me-5 flex-lg-grow-0">'
	//.'</div>'
	//	.'<div class="me-5 flex-lg-shrink-1">'
	//	.'</div>'
		.'<div class="flex-fill flex-lg-shrink-0">'
			.'<div class="float-end text-end">'
				.'<small class="opacity-75 ">'.HistoryWidget::widget(['model'=>$model]).'</small>'
				.$this->render('/attaches/model-list',compact(['model']))
			.'</div>'
		.'</div>'
	.'</div>';

$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';

$tabs=[];

$tabs[]=[
	'id'=>'users',
	'label'=>'Привязки к пользователям '.$badge.count($model->users).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-keys-users',
		'header' => 'Распределение лицензий по пользователям',
		'columns' => FieldsHelper::addFieldColumns(
			require Yii::getAlias('@app').'/views/lic-links/columns.php',
			'object',
			require Yii::getAlias('@app').'/views/users/columns.php'
		),
		'defaultOrder'=>['object.shortName','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicKeysInUsers::findLinks($model->id),'key'=>'id',]),
		'model' => new LicKeysInUsers()
	]),
];

$tabs[]=[
	'id'=>'computers',
	'label'=>'Привязки к ОС / ВМ '.$badge.count($model->comps).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-keys-comps',
		'header' => 'Распределение лицензий по операционным системам / виртуальным машинам',
		'columns' => FieldsHelper::addFieldColumns(
			require Yii::getAlias('@app').'/views/lic-links/columns.php',
			'object',
			require Yii::getAlias('@app').'/views/comps/columns.php'
		),
		'defaultOrder'=>['object.name','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicKeysInComps::findLinks($model->id),'key'=>'id',]),
		'model' => new LicKeysInComps()
	]),
];

$tabs[]=[
	'id'=>'techs',
	'label'=>'Привязки к АРМ '.$badge.count($model->arms).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'lic-keys-techs',
		'header' => 'Распределение лицензий по рабочим местам',
		'columns' => FieldsHelper::addFieldColumns(
			require Yii::getAlias('@app').'/views/lic-links/columns.php',
			'object',
			require Yii::getAlias('@app').'/views/techs/columns.php'
		),
		'defaultOrder'=>['object.num','comment','created_at','unlink'],
		'dataProvider' => new ArrayDataProvider(['allModels'=> LicKeysInArms::findLinks($model->id),'key'=>'id',]),
		'model' => new LicKeysInArms()
	]),
];

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'lic-keys-view-tab-'.$model->id,
];
