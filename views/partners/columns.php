<?php

use yii\helpers\Html;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */

return [
	'sname' => [
		'value'=>function($data) {return ModelWidget::widget(['model'=>$data,'options'=>['name'=>'medium']]);}
	],
	'inn_kpp' => [
		'value'=>function($data) {
			$tokens=[];
			if ($data->inn) $tokens[]=$data->inn;
			if ($data->kpp) $tokens[]=$data->kpp;
			return implode(' / ',$tokens);
		}
	],
	'comment'=>[
		'format'=>'ntext'
	],
];


