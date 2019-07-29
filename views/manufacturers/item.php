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

if (is_object($model)) {
?>

<spam class="soft-item">
	<?= Html::a($model->name,['/manufacturers/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/manufacturers/update','id'=>$model->id]) ?>
</spam>

<?php } else echo "Отсутствует";