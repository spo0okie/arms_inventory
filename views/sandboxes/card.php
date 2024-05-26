<?php

use app\components\HistoryWidget;
use app\components\IsArchivedObjectWidget;
use app\components\ModelFieldWidget;
use app\components\LinkObjectWidget;


/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;


?>
<?= IsArchivedObjectWidget::widget(['model'=>$model]) ?>

<?php if(!$static_view) { ?>
	<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
<?php } ?>

<h1>
	<?=  LinkObjectWidget::widget([
		'model'=>$model,
		//'confirmMessage' => 'Действительно удалить этот документ?',
		//'undeletableMessage'=>'Нельзя удалить этот документ, т.к. есть привязанные к нему объекты',
	]) ?></h1>
	<div class="mb-3">
		<?= $model->network_accessible?'Есть доступ по сети':'Недоступно по сети' ?>
	</div>
<?php
	echo ModelFieldWidget::widget(['model'=>$model,'field'=>'suffix']);
	echo ModelFieldWidget::widget(['model'=>$model,'field'=>'notepad']);
	echo ModelFieldWidget::widget(['model'=>$model,'field'=>'links']);

