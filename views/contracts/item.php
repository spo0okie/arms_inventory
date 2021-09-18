<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

//$successors=[];
if (is_object($model)) {
  //  $successors=$model->successorChain;
    if (!isset($name)) {
        $attaches=$model->sAttach;
        $name=$model->sname.' - '.$model->sAttach.(strlen($attaches)?' - ':'');
    }

    //если явно не передано активный ли документ, то вычисляем
    //если у документа если наследник полномочий, то он считается неактивным
	if (!isset($active)) $active=!is_object($model->successor);

	if (!isset($selected)) $selected=false;
?>

<span class="contracts-item <?= $selected?'contracts_selected':'' ?>">
	    <?= Html::a($name,['/contracts/view','id'=>$model->id], [
		    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/contracts/ttip','id'=>$model->id]),
		    'class'=>$active?"contract_active":"contract_inactive",
	    ]) ?>
	    <?= Html::a(
		    '<span class="fas fa-pencil-alt"/>',
		    ['/contracts/update','id'=>$model->id],
		    [
		        'title'=>'Редактировать документ',
		        'class'=>$active?"contract_active":"contract_inactive",
		    ]
	    ) ?>
</span>
<?php } else echo "Отсутствует";