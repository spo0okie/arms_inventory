<?php

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

//если ничег явно не заявлено, используем все
if (!isset($from)&!isset($count)&!isset($to)&!isset($material)) {
	$from=true;
	$material=true;
    $count=true;
    $to=true;
}
//все варианты, которые не заявлены считаем false
if (!isset($from)) $from=false;
if (!isset($material)) $material=false;
if (!isset($count)) $count=false;
if (!isset($to)) $to=false;
if (!isset($date)) $date=false;
if (!isset($cost)) $cost=false;

if (is_object($model)) {
	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'item_class'=>'materials-usages-item cursor-default',
		'name'=>
			($date?$model->date.' ':'').
			($from?($model->material->place->fullName??''):'').
			(($from&&$material)?' \ ':'').
			($material?($model->material->typeName??'material_error'):'').
			($count?(' <span class="badge bg-secondary">'.$model->count.($model->material->type->units??'').'</span>'):'').
			($cost&&$model->cost?('<span class="badge bg-success">'.$model->cost.$model->currency->symbol.'</span>'):'').
			(($count&$to)?' -&gt; ':'').
			($to?$model->to:''),
		'static'=>true,
	]);
} else echo "Отсутствует";