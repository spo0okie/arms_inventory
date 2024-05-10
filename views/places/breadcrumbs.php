<?php

/* @var $this yii\web\View */
/* @var $model Places */


use app\models\Places;

$this->params['breadcrumbs'][] = ['label' => Places::$titles, 'url' => ['index']];

//выходим на список
if (is_object($model)) {
	$model->recursiveBreadcrumbs($this);
}

