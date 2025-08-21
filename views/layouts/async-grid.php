<?php

use app\components\DynaGridWidget;
use app\helpers\StringHelper;


/* @var $this yii\web\View */
/* @var $model app\models\ArmsModel */
/* @var $searchModel app\models\ArmsModel */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $gridId string */
/* @var $source */

$modelClass=get_class($model);
echo DynaGridWidget::widget([
	'id' => $gridId,
	'pageUrl'=>$source,
	'model' => $model,
	'panel' => false,
	'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/'.StringHelper::className($modelClass).'/columns.php',
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
