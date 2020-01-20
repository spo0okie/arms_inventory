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
	foreach($models as $mat) if(is_object($mat)) {
		if (is_null($model)) $model=$mat; //если модель не выбрана, то выбираем эту
		$restGroup+=$mat->rest; //суммарный остаток
		$ids[$mat->id]=$mat->rest; //остатки
	}
	//var_dump($ids);
	asort($ids); //сортируем материалы по остаткам
	//var_dump($ids);
	$ids=array_keys($ids); //нам нужны только ключи
	
	//var_dump($ids);
	$ids=array_reverse($ids); //реверс чтобы по убыванию
	//var_dump($ids);
	$ids=array_slice($ids,0,3); //берем первые три элемента для отображения тултипа
	//var_dump($ids);
	
	if (count($ids)==1) {
		//если у нас 1 материал то ссылка будет прямо на него
		$link=['materials/view','id'=>$ids[0]];
	} else {
		//иначе на поиск
		$link=['materials/index','MaterialsSearch[model]'=>$model->place->fullName.'|'.$model->model];
	}
	
	if (is_object($model)) {
	?>
	<span
		class="material-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials/ttips','ids'=>implode(',',$ids)]) ?>"
	>
		<?= \yii\helpers\Html::a(
			($from?($model->place->fullName.'('.$model->itStaff->Ename.')'):'').
			(($from&&$material)?' \ ':'').
			($material?($model->type->name.':'.$model->model):'').
			($rest?(' '.$restGroup.$model->type->units):'')
			,
			$link
		) ?>
	</span>

<?php }} else echo "Отсутствует";