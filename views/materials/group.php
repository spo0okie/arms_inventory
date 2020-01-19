<?php

/* @var $this yii\web\View */
/* @var $models[] app\models\Materials */
if (!isset($from)&!isset($material)&!isset($rest)) {
	$from=true;
	$material=true;
	$rest=false;
}
if (!isset($from)) $from=false;				//признак рендера помещения
if (!isset($material)) $material=false;		//признак рендера материала
if (!isset($rest)) $rest=false;				//признак рендера остатка


if (is_array($models)) {
	$restGroup=0; 	//суммарный остаток группы
	$model=null;//материал который будем использовать для рендера (нам же нужен один из группы одинаковых)
	$ids=[];	//идентификаторы в группе
	foreach($models as $i=>$mat) if(is_object($mat)) {
		if (is_null($model)) $model=$mat; //если модель не выбрана, то выбираем эту
		$restGroup+=$mat->rest;
		//$ids=[]
	}
	if (is_object($model)) {
	?>
	<span
		class="material-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials/ttip','id'=>$model->id]) ?>"
	>
    <?= \yii\helpers\Html::a(
        ($from?($model->place->fullName.'('.$model->itStaff->Ename.')'):'').
        (($from&&$material)?' \ ':'').
        ($material?($model->type->name.':'.$model->model):'').
        ($rest?(' '.$restGroup.$model->type->units):'')
        ,
        ['materials/index','MaterialsSearch[model]'=>$model->place->fullName.'|'.$model->model]
    ) ?>
</span>

<?php }} else echo "Отсутствует";