<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

if (!isset($static_view)) $static_view=false;


if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="access-types-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['access-types/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['access-types/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['access-types/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>