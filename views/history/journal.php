<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use app\models\HistoryModel;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var ActiveDataProvider $dataProvider */
/** @var HistoryModel $instance */
/** @var ArmsModel $master */
/** @var string $class */

$this->params['layout-container'] = 'container-fluid';
$masterClass=get_class($master);
/** @noinspection PhpUndefinedFieldInspection */
$classTitle=$class::$title;
$classView=Inflector::camel2id(StringHelper::className($masterClass));

$this->params['breadcrumbs'][] = ['label' => $classTitle, 'url' => [$classView.'/index']];
$this->params['breadcrumbs'][] = ['label' => $master->name, 'url' => [$classView.'/view','id'=>$master->id]];
$this->params['breadcrumbs'][] = ['label' => 'История изменений'];


echo DynaGridWidget::widget([
	'id'=> Inflector::camel2id(StringHelper::className($class)).'-journal',
	'header' => $classTitle,
	'dataProvider' => $dataProvider,
	'columns' => include 'columns.php',
	'model' => $instance
]);