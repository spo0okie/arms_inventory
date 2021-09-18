<?php

use yii\helpers\Html;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=false;
if (!isset($fqdn)) $fqdn=false;

if (is_object($model)) { ?>
	
<span class="comps-item">
    <?= Html::a($model->renderName($fqdn),['/comps/view','id'=>$model->id], [
	    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/comps/ttip','id'=>$model->id]),
	    //'qtip_class'=>"qtip-wide",
    ]) ?><?= $static_view?'':Html::a('<i class="fas fa-pencil-alt"></i>',['/comps/update','id'=>$model->id])?>
</span>
<?php } else echo "Отсутствует";