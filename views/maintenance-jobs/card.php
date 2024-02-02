<?php

use app\components\ModelFieldWidget;
use app\components\LinkObjectWidget;


/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?=  LinkObjectWidget::widget([
		'model'=>$model,
	]) ?>
</h1>
<?= Yii::$app->formatter->asNtext($model->description) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'service']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'responsible']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'support']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'schedule']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'reqs']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'links']) ?>