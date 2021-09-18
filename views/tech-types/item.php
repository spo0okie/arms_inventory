<?php
/**
 * Элемент типа оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\TechTypes $model */

use yii\helpers\Html;
?>

<span class="tech_type-item">
	<?= Html::a($model->name,['tech-types/view','id'=>$model->id]) ?>
	<?= Html::a('<span class="fas fa-pencil-alt"></span>',['tech-types/update','id'=>$model->id]) ?>
</span>
