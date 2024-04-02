<?php

use app\components\HistoryWidget;
use app\components\IsHistoryObjectWidget;
use app\components\LinkObjectWidget;


/* @var $this yii\web\View */
/* @var $model app\models\ServiceConnections */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

echo IsHistoryObjectWidget::widget(['model'=>$model]);

?>

<h4>
	<?=  LinkObjectWidget::widget([
		'model'=>$model,
		//'confirmMessage' => 'Действительно удалить этот документ?',
		'undeletableMessage'=>'Нельзя удалить эту связь, т.к. есть привязанные к ней объекты',
	]) ?>
</h4>
<?= $model->restOfComment ?>
<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
<div class="d-flex flex-row align-items-center mt-3">
	<div>
		<?= $this->render('initiator',['model'=>$model]) ?>
	</div>
	<div class="mx-3">
		<h3><i class="fas fa-angle-double-right"></i></h3>
	</div>
	<div>
		<?= $this->render('target',['model'=>$model]) ?>
	</div>
</div>
<?php
