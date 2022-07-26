<?php

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

if (!isset($href)) $href=false;
if (!isset($static_view)) $static_view=true;

if (is_object($model)) {
	?>
    <span
            class="org-phones-item cursor-default"
            qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])?>"
    >
    <?= $href?\yii\helpers\Html::a($model->title,['org-phones/view','id'=>$model->id]):$model->title ?>
	<?= $static_view?\yii\helpers\Html::a('<span class="fas fa-pencil-alt"></span>',['org-phones/update','id'=>$model->id]):$model->title ?>
</span>

<?php } else echo "Отсутствует";