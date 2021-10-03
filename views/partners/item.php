<?php
/**
 * Элемент услуги связи
 * User: spookie
 * Date: 03.01.2019
 * Time: 01:21
 */

/* @var \app\models\Partners $model */

use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;
?>

<span class="partners-item"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/partners/ttip','id'=>$model->id])?>"
>
	<?= Html::a($model->bname,['partners/view','id'=>$model->id]) ?>
	<?= $static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['partners/update','id'=>$model->id]) ?>
</span>
