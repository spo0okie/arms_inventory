<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */

if (!isset($static_view)) $static_view=false;

$hrefProps=[];



if (is_object($model)) {
	if (!isset($no_ttip)) $hrefProps['qtip_ajxhrf']=\yii\helpers\Url::to(['/arms/ttip','id'=>$model->id]);
	?>
<span class="arms-item">
    <?=
		Html::a($model->num,['/arms/view','id'=>$model->id], $hrefProps)
	?><?=
		$static_view?'':Html::a('<span class="fas fa-pencil-alt"/>',['/arms/update','id'=>$model->id,'return'=>'previous'])
	?>
</span>

<?php } else echo "Отсутствует";