<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\HistoryModel;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var ActiveDataProvider $dataProvider */
/** @var HistoryModel $instance */
/** @var ArmsModel $master */
/** @var string $class */

$this->params['layout-container'] = 'container-fluid';
/** @var ArmsModel $masterClass */
$masterClass=get_class($master);
/** @noinspection PhpUndefinedFieldInspection */
$classTitle=$class::$title;
$classView=Inflector::camel2id(StringHelper::className($masterClass));

$this->params['breadcrumbs'][] = ['label' => $masterClass::$titles, 'url' => [$classView.'/index']];
$this->params['breadcrumbs'][] = ['label' => $master->name, 'url' => [$classView.'/view','id'=>$master->id]];
$this->params['breadcrumbs'][] = ['label' => 'История изменений'];


$columnsMode=Yii::$app->request->get('columnsMode','non-empty');

$modesHints=[
	'changed'=>'Показаны только с изменениями',
	'non-empty'=>'Показаны только колонки со значениями',
	'all'=>'Показаны все колонки',
];

$modesLinks=[
	'changed'=>Html::a('Показать только измененные',['journal','columnsMode'=>'changed']+Yii::$app->request->get()),
	'non-empty'=>Html::a('Показать только не пустые',['journal','columnsMode'=>'non-empty']+Yii::$app->request->get()),
	'all'=>Html::a('Показать все',['journal','columnsMode'=>'all']+Yii::$app->request->get()),
];

unset($modesLinks[$columnsMode]);



echo DynaGridWidget::widget([
	'id'=> Inflector::camel2id(StringHelper::className($class)).'-journal',
	'header' => $classTitle,
	'dataProvider' => $dataProvider,
	'columns' => include 'columns.php',
	'model' => $instance,
	'createButton' => $modesHints[$columnsMode].': '.implode(' // ',$modesLinks),
]);