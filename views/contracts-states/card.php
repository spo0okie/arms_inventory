<?php

use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\ContractsStates */
/* @var $static_view boolean */

?>
<div class="tech-states-view">

    <h1><?= LinkObjectWidget::widget(['model'=>$model,'undeletableMessage'=>'Невозможно удалить статус, т.к. он используется','static'=>$static_view]) ?></h1>
	
	<?= TextFieldWidget::widget(['model'=>$model,'field'=>'descr']) ?>

</div>
