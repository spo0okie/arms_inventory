<?php

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsTypes */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/materials/ttip','id'=>$model->id]) ?->"
if (is_object($model)) { ?>
	<span
		class="material-item cursor-default"
	>
    <?= \yii\helpers\Html::a($model->name,['materials-types/view','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";