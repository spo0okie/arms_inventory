<?php

use app\components\DynaGridWidget;
use app\models\Materials;
use app\models\MaterialsTypes;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $groupBy string */


$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => MaterialsTypes::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->params['headerContent']=$this->render('header',compact('model'));
$this->params['layout-container']='container-fluid px-0';

$showTypes=false;

if ($groupBy=='name') {
	echo DynaGridWidget::widget([
		'id' => 'materials-types-' . $groupBy . '-groups1',
		'header' => Html::encode(Materials::$titles),
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/materials/groups-columns.php',
		'defaultOrder' => ['place', 'model', 'rest'],
		'createButton' => Html::a('Добавить', ['materials/create','Materials'=>['type_id'=>$model->id]], ['class' => 'btn btn-success me-3'])
			. Html::a('Показать подробно', ['view','groupBy'=>null]+Yii::$app->request->get()),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]);
} else {
	echo DynaGridWidget::widget([
		'id' => 'materials-types-groups',
		'header' => Html::encode(Materials::$titles),
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/materials/columns.php',
		'defaultOrder' => ['place', 'model', 'comment', 'rest'],
		'createButton' => Html::a('Добавить', ['materials/create','Materials'=>['type_id'=>$model->id]], ['class' => 'btn btn-success me-3'])
			. Html::a('Группировать по наименованию', ['view','groupBy'=>'name']+Yii::$app->request->get()),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]);
}