<?php

use kartik\dynagrid\DynaGrid;
use kartik\grid;

/**
 * Рендер списка, вынесен отдельным компонентом, т.к. нужен много где
 */

/* @var $this yii\web\View */
/* @var $filterModel yii\base\Model */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $columns[] */
/* @var $id */
/* @var $header */
/* @var $createButton */
/* @var $hintButton */




echo DynaGrid::widget([
	'storage'=>DynaGrid::TYPE_COOKIE,
	'columns' => $columns,
	'gridOptions'=>[
		'panel'=>[
			'type' => grid\GridView::TYPE_DEFAULT,
			'heading' => $header,
			'before' => $createButton,
		],
		'toolbar' => [
			['content'=>'{dynagridFilter}{dynagridSort}{dynagrid}'],
			['content'=>'{export}'],
			['content'=>$hintButton],
		],
		'condensed' => true,
		'dataProvider' => $dataProvider,
		'filterModel' => $filterModel,
		'tableOptions' => ['class'=>'table-condensed table-striped table-bordered arms_index'],
		'resizableColumns'=>true,
		'persistResize'=>true
	],
	'options'=>[
		'id'=>'dynaGrid-'.$id,
	]
]);

