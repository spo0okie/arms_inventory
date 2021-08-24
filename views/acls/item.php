<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

if (!isset($static_view)) $static_view=false;


if (!empty($model)) {
	if (!isset($name)) $name=$model->sname;
	?>

	<span class="acls-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['acls/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['acls/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="glyphicon glyphicon-pencil"></span>',['acls/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>