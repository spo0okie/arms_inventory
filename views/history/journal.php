<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;
use app\models\HistoryModel;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\web\View;

/** @var View $this */
/** @var ActiveDataProvider $dataProvider */
/** @var HistoryModel$instance */
/** @var string $class */

$this->params['layout-container'] = 'container-fluid';

/** @noinspection PhpUndefinedFieldInspection */
echo DynaGridWidget::widget([
	'id'=> Inflector::camel2id(StringHelper::className($class)).'-journal',
	'header' => $class::$title,
	'dataProvider' => $dataProvider,
	'columns' => include 'columns.php',
]);