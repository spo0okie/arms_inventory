<?php

use app\components\LinkObjectWidget;
use app\components\DynaGridWidget;
use app\models\TechTypes;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $searchModel /app/models/TechsSearch */
/* @var $dataProvider ActiveDataProvider */

Url::remember();
$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)

$techModels=$model->techModels;

// Общая шапка учитывает модели оборудования через обратную связь tech_models_ids.
$this->params['headerContent'] = '<h2>'.LinkObjectWidget::widget([
	'model' => $model,
	'name' => Html::encode($this->title),
	'hideUndeletable' => false,
	'confirmMessage' => 'Удалить этот тип оборудования?',
]).'</h2>';

$tabs=[];
$tabs[] = [
	'id'=>'techs',
	'label'=>'Экземпляры оборудования',
	'content'=>DynaGridWidget::widget([
			'id' => 'tech-types-arms-index',
			'header' => 'Оборудование',
			'columns' => require __DIR__.'/../techs/columns.php',
			'defaultOrder' => $model->is_computer?
				['attach','num','model','comp_id','ip','mac','state_id','user','place','inv_sn']:
				['attach','num','model','ip','mac','state_id','user','place','inv_sn'],
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
			'resizableColumns' => true,
	]),
];

$tabs[] = [
	'id'=>'models',
	'label'=>'Список моделей',
	'content'=>$this->render('list-models',['model'=>$model,'techModels'=>$techModels]),
];


$this->params['navTabs'] = $tabs;
$this->params['tabsParams']=[
	'cookieName'=>'techmodels-view-tab-'.$model->id
];
