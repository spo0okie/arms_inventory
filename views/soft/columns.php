<?php

use kartik\editable\Editable;
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
	'comment'=>[
		'class'=>'kartik\grid\EditableColumn',
		'editableOptions'=> [
				'name'=>'comment',
				'header'=>'Комментарий',
				'format'=>Editable::FORMAT_LINK,
				'inputType' => Editable::INPUT_TEXT,
				'inlineSettings' => [
					'templateBefore'=>'<div class="kv-editable-form-inline d-flex w-100 g-0 m-0"><div class="mb-2">{loading}</div>',
				],
				'asPopover' => false,
				'buttonsTemplate'=>'{submit}',
				'options' => [
					'class' => 'w-100',
					'placeholder'=>'Введите пояснение...',
				],
				'containerOptions'=>['class'=>'w-100 p-0 m-0'],
				'contentOptions'=>['class'=>'p-0 m-0'],
				'inputFieldConfig'=>['options'=>['class'=>'flex-grow-1']],
				'editableValueOptions'=>['class'=>'p-0 m-0 border-0 text-start bg-transparent',],
				'formOptions' => [
					'action' => [
						'/soft/editable',
					]
				],
			],
	],
	'hitsCount',
	'compsCount',
	'licGroupsCount',
];
