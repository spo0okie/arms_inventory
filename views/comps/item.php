<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
if (is_object($model)) {
	if (!isset($fqdn)) $fqdn=false;
	$name = $fqdn?mb_strtolower($model->fqdn):$model->name;
?>
<span class="comps-item">
    <?= Html::a($name,['/comps/view','id'=>$model->id], [
	    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/comps/ttip','id'=>$model->id]),
	    //'qtip_class'=>"qtip-wide",
    ]) ?>
    <?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/comps/update','id'=>$model->id])?>
</span>
<?php } else echo "Отсутствует";