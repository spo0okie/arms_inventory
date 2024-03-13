<?php

/* @var $this yii\web\View */
/* @var $model Places */


use app\models\Places;

$this->params['breadcrumbs'][] = ['label' => Places::$titles, 'url' => ['index']];

//выходим на список
if (is_object($model)) {
	$item=$model;
	$chain=[$model];
	while (is_object($item=$item->parent)) {
		$chain[]=$item;
	}
	foreach (array_reverse($chain) as $item) {
		$this->params['breadcrumbs'][]=[
			'label'=>$item->name,
			'url'=>['/places/view','id'=>$item->id]
		];
	}
}

