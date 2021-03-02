<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

if (!isset($static_view)) $static_view=false;
if (!isset($include_tech)) $include_tech=false;
if (!isset($reverse)) $reverse=false;

if (!empty($model)) {
	if ($include_tech && !$reverse) {
		echo $this->render('/techs/item', ['model'=>$model->tech,'static_view'=>true]).\app\models\Ports::$tech_postfix;
	}

	if (!isset($name)) $name=$model->name;
	?>

	<span class="ports-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['ports/ttip','id'=>$model->id]) ?>"
	>
		<?=  !$static_view&&$reverse?Html::a('<span class="glyphicon glyphicon-pencil"></span>',['ports/update','id'=>$model->id,'return'=>'previous']):'' ?>
		<?=  Html::a(\app\models\Ports::$port_prefix.$name,['ports/view','id'=>$model->id]) ?>
		<?=  !$static_view&&!$reverse?Html::a('<span class="glyphicon glyphicon-pencil"></span>',['ports/update','id'=>$model->id,'return'=>'previous']):'' ?>
	</span>
<?php
	if ($include_tech && $reverse) {
		echo \app\models\Ports::$tech_postfix.' '.$this->render('/techs/item', ['model'=>$model->tech,'static_view'=>true]);
	}
} ?>