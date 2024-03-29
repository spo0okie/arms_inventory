<?php

use app\helpers\ArrayHelper;
use app\models\HistoryModel;
use app\components\ListObjectsWidget;
use yii\data\ActiveDataProvider;
use yii\web\View;

/** @var View $this */
/** @var HistoryModel $instance */
/** @var ActiveDataProvider $dataProvider */
/** @var string $columnsMode */

$renderer=$this;
$columns=[];


$attributes=$instance->attributes();
//сразу выкидываем поля которые не нужно отображать
$attributes=array_diff($attributes,['id','master_id','changed_attributes']);

if ($columnsMode=='non-empty') {
	$nonEmpty=[];
	foreach ($attributes as $attribute) {
		if (!in_array($attribute,$nonEmpty)) {
			foreach ($dataProvider->models as $model) {
				if (!empty($model->$attribute)) {
					$nonEmpty[]=$attribute;
					break;
				}
			}
		}
	}
	if (count($nonEmpty))
		$attributes=$nonEmpty;
}

if ($columnsMode=='changed') {
	$changed=[];
	if (in_array('updated_at',$attributes)) $changed[]='updated_at';
	if (in_array('updated_by',$attributes)) $changed[]='updated_by';
	$firstId=min(ArrayHelper::getArrayField($dataProvider->models,'id'));
	foreach ($attributes as $attribute) {
		if (!in_array($attribute,$changed)) {
			foreach ($dataProvider->models as $model) if ($model->id != $firstId) {
				/** @var HistoryModel $model */
				if ($model->attributeIsChanged($attribute)) {
					$changed[]=$attribute;
					break;
				}
			}
		}
	}
	if (count($changed))
		$attributes=$changed;
}

//первым столбцом бы дату изменения
if (in_array('updated_at',$attributes)) {
	$columns['updated_at']=[
		'label'=>'Время',
		'format'=>'raw',
	];
	$attributes=array_diff($attributes,['updated_at']); //выкидываем из пула
}

//вторым бы автора изменения
if (in_array('updated_by',$attributes)) {
	$columns['updated_by']=[
		'label'=>'Автор',
		'value'=>function($data) use($renderer) {
			/** @var HistoryModel $data */
			if (is_object($user=$data->getUpdatedByUser())) {
				return $renderer->render('/users/item',['model'=>$user,'short'=>true,'static_view'=>true]);
			}
			return $data->updated_by;
		}
	];
	$attributes=array_diff($attributes,['updated_by']);  //выкидываем из пула
}

//комментарий
if (in_array('updated_comment',$attributes)) {
	$columns[]='updated_comment';
	$attributes=array_diff($attributes,['updated_comment']);  //выкидываем из пула
}

foreach ($attributes as $attribute) {
	$attrData=$instance->getAttributeData($attribute);
	$columns[$attribute]=isset($attrData['column'])?$attrData['column']:[];
	
	$columns[$attribute]['contentOptions']=function ($data) use ($attribute) {
		/* @var $data HistoryModel */
		return $data->attributeIsChanged($attribute)?[
			'class'=>'table-warning'
		]:[];
	};
	
	if ($instance->attributeIsLink($attribute)) {
		$columns[$attribute]['value']=function($data) use($attribute) {
			$models=$data->fetchLinks($attribute);
			if (!is_array($models)) $models=[$models];
			/** @var HistoryModel $data */
			return ListObjectsWidget::widget([
				'title'=>false,
				'models'=>$models,
				'glue'=>'<br/>',
				'lineBr'=>false,
				'item_options'=>['static_view'=>true]
			]);
		};
	}
	
}

return $columns;