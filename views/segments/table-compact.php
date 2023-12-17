<?php

use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
echo GridView::widget([
	'dataProvider' => $dataProvider,
	'columns' => [
		[
			'attribute'=>'name',
			'format'=>'raw',
			'value'=>function($data) use ($renderer){
				return $renderer->render('item',['model'=>$data]);
			},
			'contentOptions'=>['class'=>'text-nowrap']
		],
		'description:ntext',
	],
	'layout'=>'{items}'
]);
