<?php

use app\components\IsHistoryObjectWidget;
use app\components\ModelFieldWidget;
use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>
<?= IsHistoryObjectWidget::widget(['model'=>$model]) ?>
<h1>
	<?=  LinkObjectWidget::widget([
		'model'=>$model,
	]) ?>
</h1>
<?= TextFieldWidget::widget(['model'=>$model,'field'=>'description']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'service']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'responsible']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'support']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'schedule']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'reqs']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'links']) ?>