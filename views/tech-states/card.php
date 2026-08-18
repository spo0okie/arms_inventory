<?php

use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\TechStates */
/* @var $static_view boolean */

?>
<div class="tech-states-view">

    <h1><?= LinkObjectWidget::widget(['model'=>$model,'undeletableMessage'=>'Невозможно удалить статус, т.к. он используется','static'=>$static_view]) ?></h1>

	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'descr') ?>

</div>
