<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;


/** @var yii\web\View $this */
/* @var $model \app\models\base\ArmsModel */
/* @var $searchModel \app\models\base\ArmsModel */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $gridId string */
/* @var $source */

$modelClass=get_class($model);
$columnsFile=$this->findViewFile($model->viewsPath.'/columns.php');

if (!file_exists($columnsFile)) {
	throw new \yii\web\HttpException(404,'Grid columns file missing: "'.$columnsFile.'"');
}

echo DynaGridWidget::widget([
	'id' => $gridId,
	'pageUrl'=>$source,
	'model' => $model,
	'panel' => false,
	'columns' => include $columnsFile,
	'defaultOrder' => $modelClass::$defaultColumns??[],
	'filterModel' => $searchModel,
	'dataProvider' => $dataProvider,
	'toggleButtonGrid'=>[
		'label' => '<i class="fas fa-wrench fa-fw"></i>',
		'title' => 'Персонализировать настройки таблицы',
		'data-pjax' => false,
		'class' => 'd-none',	//кнопка настроек скрыта, т.к. выносится на вкладку, которая подгружает эту таблицу
	],
	'gridOptions' => [
		'layout'=>'{dynagrid}{items}{export}',
		'showFooter' => false,
		'pjax' => true,
		'pjaxSettings' => [
			'options'=>[
				'timeout'=>30000,
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			],
		],
	],
]);
