<?php

use app\models\AccessTypes;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;

return [
	'name'=>['options'=>['noDelete'=>true]],
	'code',
	'comment',
	'notepad',
	//['class' => 'yii\grid\ActionColumn'],
];
