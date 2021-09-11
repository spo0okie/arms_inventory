<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
?>
<span class="arms-item">
    <?= Html::a($model->num,['/arms/view','id'=>$model->id], [
	    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/arms/ttip','id'=>$model->id]),
	    //'qtip_class'=>"qtip-wide",
    ]) ?><?= $static_view?'':Html::a('<span class="glyphicon glyphicon-pencil"/>',['/arms/update','id'=>$model->id,'return'=>'previous'],
	    [
	    //'qtip_ajxhrf'=>\yii\helpers\Url::to(['/login-journal/ttip','id'=>$model->id]),
	    //'qtip_class'=>"qtip-wide",
    ]) ?>
</span>

<?php } else echo "Отсутствует";