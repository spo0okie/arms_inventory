<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */

return [
	'sname' => [
		'value'=>function($data) {return $this->render('/partners/item',['model'=>$data,'name'=>'medium']);}
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
