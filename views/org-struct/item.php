<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>

	<span class="org-struct-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['org-struct/ttip','id'=>$model->id,'org_id'=>$model->org_id]) ?>"
	>
		<?=  $name ?>
		
		<?php /*  Html::a($name,['org-struct/view','id'=>$model->id]) ?>
		<?=  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['org-struct/update','id'=>$model->id,'return'=>'previous']) */ ?>
	</span>
<?php } ?>