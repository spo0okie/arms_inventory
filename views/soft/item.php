<?php
/** Элемент софта
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use yii\helpers\Html;

/* @var $model \app\models\Soft */
?>

<spam class="soft-item">
	<?= Html::a($model->descr,['/soft/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/soft/update','id'=>$model->id]) ?>
</spam>