<?php
/**
 * Список объектов привязанных к лицензии
 * User: spookie
 * Date: 10.05.2022
 * Time: 13:21
 */

/* @var ArrayDataProvider $dataProvider */

use app\models\links\LicLinks;
use kartik\editable\Editable;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;

$renderer=$this;


return [
	'comment'=>[
		'class'=>'kartik\grid\EditableColumn',
		'label'=>'Комментарий',
		'editableOptions'=> function ($model, $key, $index) { return [
			'name'=>'comment',
			'header'=>'Комментарий',
			'format'=>Editable::FORMAT_LINK,
			'inputType' => Editable::INPUT_TEXT,
			'inlineSettings' => [
				'templateBefore'=>'<div class="kv-editable-form-inline d-flex w-100 g-0 m-0"><div class="mb-2">{loading}</div>',
			],
			'asPopover' => false,
			'value' => $model['comment'],
			'buttonsTemplate'=>'{submit}',
			'options' => [
				'class' => 'w-100',
				'placeholder'=>'Введите комментарий...',
				'pluginOptions'=>[
					'maxLength'=>255
				],
			],
			//'key'=>$model->id,
			'containerOptions'=>['class'=>'row p-0 m-0'],
			'contentOptions'=>['class'=>'p-0 m-0'],
			'inputFieldConfig'=>['options'=>['class'=>'flex-grow-1']],
			'editableValueOptions'=>['class'=>'p-0 text-start kv-editable-link',],
			'formOptions' => [
				'action' => [
					'/lic-links/update-lic-'.$model->licType.'-in-'.$model->objType,
				]
			],
		];},
	],
	'created_at'=>[
		'label'=>'Когда',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			
			/**
			 * @var $item LicLinks
			 */
			$value=Yii::$app->formatter->asDate($item->created_at);
			if (is_object($item->creator))
				$value.=' '.$item->creator->getShortName();
			
			$ttip='';
			if ($item->updated_at) {
				$ttip='Обновлено '. Yii::$app->formatter->asDate($item->updated_at);
				if (is_object($item->updater))
					$ttip.=' ('.$item->updater->getShortName().')';
			}
			if (strlen($ttip))
				$ttip='qtip_ttip="'.$ttip.'"';
			
			return '<span '.$ttip.'>'.$value.'</span>';
		},
	],
	
	'unlink'=>[
		'header'=>'<span class="fas fa-trash"/>',
		'format'=>'raw',
		'value'=>function($item) use ($renderer){
			return Html::a('<span class="fas fa-trash"/>',
				[
					'/lic-'.$item->licType.'/unlink',
					'id'=>$item->lic->id,
					$item->objType.'_id'=>$item->object->id
				],
				[
					'data'=>['confirm' => 'Отвязать лицензию от '.$item->objName.'?',],
					'qtip_ttip'=>'Удалить закрепление лицензии'
				]
			);
		},
	],
];
