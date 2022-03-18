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
	?>
	<span
		class="material-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials/ttip','id'=>$model->id]) ?>"
	>
    <?= \yii\helpers\Html::a(
        ($from?($model->place->fullName.($responsible?'('.$model->itStaff->Ename.')':'')):'').
        (($from&&$material)?' \ ':'').
        ($material?($model->type->name.':'.$model->model):'').
        ($rest?(' '.$model->rest.$model->type->units):'')
        ,
        ['materials/view','id'=>$model->id]
    ) ?>
</span>

<?php } else echo "Отсутствует";