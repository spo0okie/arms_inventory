<?php

use app\components\DynaGridWidget;
use app\components\ModelFieldWidget;
use app\components\TabsWidget;
use app\models\Techs;
use app\models\Users;
use app\models\UsersSearch;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */
/* @var $contracts app\models\Contracts[] */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel UsersSearch */

Url::remember();

if (!isset($contracts)) $contracts=$model->docs;

$this->title = $model->sname;
//крошки собираются автоматически в layout (views/layouts/main.php)
YiiAsset::register($this);

//шапка страницы: карточка контрагента + его файлы и услуги
$this->params['headerContent']=
	'<div class="d-flex flex-row flex-wrap">'
		.'<div class="pe-5 flex-fill">'
			.$this->render('card',['model'=>$model])
		.'</div>'
		.'<div class="flex-fill">'
			.$this->render('/attaches/model-list',compact(['model']))
			.ModelFieldWidget::widget(['model'=>$model,'field'=>'services'])
		.'</div>'
	.'</div>';

$tabs=[];

$tabs[]=[
	'id'=>'orgStruct',
	'label'=>'Орг. структура'
		.TabsWidget::badgeStart.count($model->orgStructs).TabsWidget::badgeEnd,
	'content'=>Html::a('Добавить подразделение',[
			'org-struct/create','OrgStruct[org_id]'=>$model->id
		],[
			'class'=>'badge text-bg-success m-0'
		])
		.$this->render('/org-struct/tree-list',['models'=>$model->orgStructs]),
	'headerOptions'=>['class'=>!count($model->orgStructs)?'muted-tab':''],
];

$tabs[]=[
	'id'=>'users',
	'label'=>'Сотрудники'
		.TabsWidget::badgeStart.$dataProvider->totalCount.TabsWidget::badgeEnd,
	'content'=>DynaGridWidget::widget([
		'id' => 'org-struct-users-index',
		'columns' => require __DIR__.'/../users/columns.php',
		'header' => false,
		'defaultOrder' => ['employee_id','shortName','Doljnost','orgStruct_name','Login','Email','Phone','arms','Mobile'],
		'createButton' => Html::a('Новый '.Users::$title,[
			'users/create','Users[org_id]'=>$model->id
		],[
			'class'=>'btn btn-success open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]),
	'headerOptions'=>['class'=>!$dataProvider->totalCount?'muted-tab':''],
];

//колонки оборудования подключаем в изолированной области видимости,
//чтобы columns.php не подхватил $searchModel (здесь это UsersSearch)
$techsColumns=(function() {
	return require Yii::getAlias('@app').'/views/techs/columns.php';
})();

$tabs[]=[
	'id'=>'techs',
	'label'=>'Оборудование'
		//relationForGrid: жадная загрузка связей под видимые колонки грида
		//(join-аннотации attributeData), иначе каждая строка грузит связи отдельными запросами
		.TabsWidget::badgeStart.count($model->relationForGrid('techs','partners-techs-list')).TabsWidget::badgeEnd,
	'content'=>DynaGridWidget::widget([
		'id' => 'partners-techs-list',
		'header' => false,
		'columns' => $techsColumns,
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->techs]),
		'model' => new Techs()
	]),
	'headerOptions'=>['class'=>!count($model->techs)?'muted-tab':''],
];

$tabs[]=[
	'id'=>'docs',
	'label'=>'Документы'
		.TabsWidget::badgeStart.count($contracts).TabsWidget::badgeEnd,
	'content'=>ModelFieldWidget::widget([
		'model'=>$model,
		'field'=>'docs',
		'title'=>false,
		'item_options'=>['partner'=>false,'show_payment'=>true],
		'glue'=>'<br/>'
	]),
	'headerOptions'=>['class'=>!count($contracts)?'muted-tab':''],
];

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'partners-view-tab-'.$model->id,
];
