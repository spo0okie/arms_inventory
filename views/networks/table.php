<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;

if (!isset($panel)) $panel=false;

if (!isset($columns)) $columns=['name','segment','comment','vlan','domain','usage'];

$override=[];
if (!$panel) $override['panel']=false;

echo \app\components\DynaGridWidget::widget(\app\helpers\ArrayHelper::recursiveOverride([
	'id'=>'networks-index',
	'header' => \app\models\Networks::$titles,
	'createButton' => Html::a('Новая', ['create'], ['class' => 'btn btn-success']),
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	//'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	'columns' => include 'columns.php',
	'gridOptions'=>[
		'pjax' => true,
		'pjaxSettings' => [
			'neverTimeout'=>true,
		],
	],
	'defaultOrder'=>$columns,
],$override));