<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (is_object($model)) {
	?>
	<span
		class="org-inet-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-inet/ttip','id'=>$model->id]) ?>"
	>
    <?= \yii\helpers\Html::a($model->name,['org-inet/view','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";