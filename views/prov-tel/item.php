<?php
/**
 * Элемент услуги связи
 * User: spookie
 * Date: 03.01.2019
 * Time: 01:21
 */

/* @var \app\models\ProvTel $model */

use yii\helpers\Html;
?>

<span class="prov_tel-item"
      qtip_ajxhrf="<?= \yii\helpers\Url::to(['/prov-tel/ttip','id'=>$model->id])?>"
>
	<?= Html::a($model->name,['prov-tel/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="glyphicon glyphicon-pencil"></span>',['prov-tel/update','id'=>$model->id]) ?>
</span>
