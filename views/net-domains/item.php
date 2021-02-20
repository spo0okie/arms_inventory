<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="net-domains-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['net-domains/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['net-domains/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['net-domains/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } ?>