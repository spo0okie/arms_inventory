<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\bootstrap5\Tabs;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
/* @var $searchModel /app/models/TechsSearch */
/* @var $dataProvider \yii\data\ActiveDataProvider */

\yii\helpers\Url::remember();
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$techModels=$model->techModels;


$this->params['headerContent']='<h2>'.
        Html::encode($this->title).' '.
        Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]).
        (!count($techModels)?Html::a('<span class="fas fa-trash"></span>', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Удалить этот тип оборудования?',
		        'method' => 'post',
	        ],
        ]):'').
    	'</h2>'.
		(
			count($techModels)?
			'<p class="small">
				<span class="fas fa-exclamation-triangle"></span> Невозможно удалить этот тип оборудования, т.к. заведены модели оборудования этого типа. (см ниже)
			</p>':''
		);
	


$tabs = [
	[
		'label'=>'Список моделей',
		'linkOptions'=>['id'=>'models'],
		'content'=>'<div class="container">'.
			$this->render('list-models',['model'=>$model,'techModels'=>$techModels]).
		'</div>',
	],
];

$tabs[] = [
	'label'=>'Экземпляры рабочих мест',
	'linkOptions'=>['id'=>'items'],
	'content'=>'<div class="container-fluid">'.DynaGridWidget::widget([
			'id' => 'tech-types-arms-index',
			'header' => 'Оборудование',
			'columns' => require __DIR__.'/../techs/columns.php',
			'defaultOrder' => $model->is_computer?
				['attach','num','model','comp_id','ip','mac','state_id','user','place','inv_sn']:
				['attach','num','model','ip','mac','state_id','user','place','inv_sn'],
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
			'resizableColumns' => true,
	]).'</div>' ,
	'active'=>is_array(Yii::$app->request->get('TechsSearch'))||Yii::$app->request->get('page')||Yii::$app->request->get('sort')
];


$this->params['navTabs'] = $tabs;

