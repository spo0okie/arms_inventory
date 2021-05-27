<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */

$ttip='Серийный номер: '.($model->sn?$model->sn:' отсутствует ').
	'<br />'.
	'Инвентарный номер (бухг.):'.($model->inv_num?$model->inv_num:' отсутствует ');

$tokens=[];

if (strlen($model->sn)) $tokens[]=$model->sn;
if (strlen($model->inv_num)) $tokens[]=$model->inv_num;

if (count($tokens)) { ?>
	<span qtip_ttip="<?= $ttip ?>">
		<?= implode(', ',$tokens) ?>
	</span>
<?php } ?>

