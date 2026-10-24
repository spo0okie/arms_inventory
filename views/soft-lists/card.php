<?php

use yii\helpers\Html;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */

if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= LinkObjectWidget::widget([
		'model' => $model,
		'name' => Html::encode($model->descr),
		'static' => $static_view,
		'hideUndeletable' => false,
		'confirmMessage' => 'Удалить этот элемент? Действие необратимо',
	]) ?>
</h1>

<span class="small"><?= \app\components\ModelFieldWidget::renderFieldValue($model,'name') ?></span>
<p><?= \app\components\ModelFieldWidget::renderFieldValue($model,'comment') ?> </p>
