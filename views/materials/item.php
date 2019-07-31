<?php

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (is_object($model)) {
	?>
	<span
		class="material-item cursor-default"
		qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials/ttip','id'=>$model->id]) ?>"
	>
    <?= \yii\helpers\Html::a($model->place->fullName.'('.$model->itStaff->Ename.') \ '.$model->materialType->name.':'.$model->model,['materials/view','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";