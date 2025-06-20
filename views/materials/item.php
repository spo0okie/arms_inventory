<?php

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (!isset($from)&!isset($material)&!isset($rest)&!isset($responsible)) {
	$from=true;
	$material=true;
	$responsible=true;
	$rest=false;
}

if (!isset($from)) $from=false;
if (!isset($material)) $material=true;
if (!isset($rest)) $rest=false;
if (!isset($responsible)) $responsible=false;


if (is_object($model)) {
	if (!isset($name)) $name=
		($from?($model->place->fullName.($responsible?'('.$model->itStaff->Ename.')':'')):'').
		(($from&&$material)?' \ ':'').
		($material?$model->typeName:'').
		($rest?(' '.$model->rest.($model->type?$model->type->units:'type_error')):'');

	echo \app\components\ItemObjectWidget::widget([
		'model'=>$model,
		'noDelete'=>true,
		'static'=>true,
		'name'=>$name,
	]);
} else echo "Отсутствует";