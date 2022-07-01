<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

if (!isset($href)) $href=false;

if (is_object($model)) {
	?>
    <span
            class="org-phones-item cursor-default"
            qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])?>"
    >
    <?= $href?\yii\helpers\Html::a($model->title,['org-phones/view','id'=>$model->id]):$model->title ?>
</span>

<?php } else echo "Отсутствует";