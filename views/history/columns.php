<?php

use app\models\HistoryModel;
use app\components\ListObjectsWidget;
use yii\web\View;

/** @var View $this */
/** @var HistoryModel $instance */

$renderer=$this;
$columns=[];


$attributes=$instance->attributes();
//сразу выкидываем поля которые не нужно отображать
$attributes=array_diff($attributes,['id','master_id','changed_attributes']);


//первым столбцом бы дату изменения
if (in_array('updated_at',$attributes)) {
	$columns['updated_at']=['format'=>'datetime'];
	$attributes=array_diff($attributes,['updated_at']); //выкидываем из пула
}

//вторым бы автора изменения
if (in_array('updated_by',$attributes)) {
	$columns['updated_by']=['value'=>function($data) use($renderer) {
		/** @var HistoryModel $data */
		if (is_object($user=$data->getUpdatedByUser())) {
			return $renderer->render('/users/item',['model'=>$user,'short'=>true,'static_view'=>true]);
		}
		return $data->updated_by;
	}];
	$attributes=array_diff($attributes,['updated_by']);  //выкидываем из пула
}

//комментарий
if (in_array('updated_comment',$attributes)) {
	$columns[]='updated_comment';
	$attributes=array_diff($attributes,['updated_comment']);  //выкидываем из пула
}

foreach ($attributes as $attribute) {
	$columns[$attribute]=[
		'contentOptions'=>function ($data) use ($attribute) {
			/* @var $data HistoryModel */
			return $data->attributeIsChanged($attribute)?[
				'class'=>'table-warning'
			]:[];
		}
	];
	
	if ($instance->attributeIsLink($attribute)) {
		$columns[$attribute]['value']=function($data) use($attribute) {
			/** @var HistoryModel $data */
			return ListObjectsWidget::widget([
				'title'=>false,
				'models'=>$data->fetchLinks($attribute)
			]);
		};
	}
}

return $columns;