<?php
/**
 * Элемент производитель
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use yii\helpers\Html;

/* @var $model \app\models\Manufacturers */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) {
?>

<span class="manufacturers-item"
	qtip_ajxhrf="<?= \yii\helpers\Url::to(['/manufacturers/ttip','id'=>$model->id])?>"
>
	<?= Html::a($model->name,['/manufacturers/view','id'=>$model->id]) ?>
	
	<?php if(!$static_view) echo Html::a('<span class="fas fa-pencil-alt"/>',['/manufacturers/update','id'=>$model->id]) ?>
</span>

<?php } else echo "Отсутствует";