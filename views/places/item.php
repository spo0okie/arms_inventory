<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])
//qtip_class="qtip-wide"
//	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/places/update','id'=>$model->id])

if (is_object($model)) {

?>
<span class="places-item" >
	<?= Html::a(
        (isset($short))?$model->short:(isset($full)?$model->fullName:$model->name),
        ['/places/view','id'=>$model->id],
        ['qtip_ttip'=>$model->name,]
        )
    ?>
</span>

<?php } else echo "Отсутствует";