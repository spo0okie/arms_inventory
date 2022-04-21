<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
	?>
	<span
		class="org-inet-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-inet/ttip','id'=>$model->id]) ?>"
	>
    <?= \yii\helpers\Html::a($model->name,['org-inet/view','id'=>$model->id]) ?>
	<?= $static_view?'':\yii\helpers\Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
</span>

<?php } else echo "Отсутствует";