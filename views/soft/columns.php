<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$renderer=$this;
$manufacturers=\app\models\Manufacturers::fetchNames();
return [
	'descr'=> [
		'value'=>function($data) use ($renderer){
			return $renderer->render('/soft/item',[
				'model'=>$data,
				'name'=>(is_object($data->manufacturer)?$data->manufacturer->name.' ':'').$data->descr
			]);
		}
	],
	'comment',
	'hits'=>[
		'header'=>\app\components\AttributeHintWidget::widget([
			'label'=>'Уст.',
			'hint'=>'Количество обнаруженных установок продукта'
		]),
		'value'=>function($data) {
			return count($data->hits);
		}
	],
	'comps'=>[
		'header'=>\app\components\AttributeHintWidget::widget([
			'label'=>'В пасп.',
			'hint'=>'Количество внесений продукта в паспорта АРМ'
		]),
		'value'=>function($data) {
			return count($data->comps);
		}
	],
];
