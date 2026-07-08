<?php

use app\components\ItemObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

if (is_object($model)) {
    if (!isset($name)) {
        $attaches=$model->sAttach;
		if (!isset($partner)) $partner=true;
		if (!isset($user)) $user=true;
        $name=$model->getSname(true,true,$partner,$user).(strlen($attaches)?' - ':'').$attaches;
    }

    if (!isset($show_payment)) $show_payment=false;

    //если явно не передано активный ли документ, то вычисляем
    //если у документа если наследник полномочий, то он считается неактивным
	if (!isset($active)) $active=!is_object($model->successor);

	if (!isset($selected)) $selected=false;
	if (!isset($static_view)) $static_view=false;

	$payment='';
	if ($show_payment && $model->total) {
		$class=is_object($model->state)?$model->state->code:'';
		$total=number_format($model->total,2,'.',' ' );
		$payment="<span class='$class'>{$total}{$model->currency->symbol}</span>";
	}

	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'nameSuffix'=>$payment,
		'item_class'=>'contracts-item'.($selected?' contracts_selected':''),
		'cssClass'=>$active?'contract_active':'contract_inactive',
		'updateHint'=>'Редактировать документ',
		'noDelete'=>true,
		'static'=>$static_view,
	]);
} else echo "Отсутствует";
