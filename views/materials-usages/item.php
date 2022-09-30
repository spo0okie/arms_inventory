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
	?>
	<span
		class="materials-usages-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials-usages/ttip','id'=>$model->id]) ?>"
	>
        <?= \yii\helpers\Html::a(
	        ($date?$model->date.' ':'').
			($from?($model->material->place->fullName):'').
			(($from&&$material)?' \ ':'').
			($material?($model->material->type->name.':'.$model->material->model):'').
			($count?(' <span class="badge bg-secondary">'.$model->count.$model->material->type->units.'</span>'):'').
			($cost&&$model->cost?('<span class="badge bg-success">'.$model->cost.$model->currency->symbol.'</span>'):'').
            (($count&$to)?' -&gt; ':'').
            ($to?$model->to:''),
            ['materials-usages/view','id'=>$model->id]
        ) ?>
</span>

<?php } else echo "Отсутствует";