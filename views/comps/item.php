<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
if (is_object($model)) {

?>
<span class="comps-item">
    <?= Html::a($model->name,['/comps/view','id'=>$model->id], [
	    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/comps/ttip','id'=>$model->id]),
	    //'qtip_class'=>"qtip-wide",
    ]) ?>
    <?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/comps/update','id'=>$model->id])?>
</span>
<?php } else echo "Отсутствует";