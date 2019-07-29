<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

if (is_object($model)) {
	?>
    <span
            class="org-phones-item cursor-default"
            qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])?>"
    >
    <?= $model->fullNum?>
</span>

<?php } else echo "Отсутствует";