<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\HwIgnore */
/* @var $static_view boolean */
?>
<div class="hw-ignore-view">
	<h1>
		<?= LinkObjectWidget::widget([
			'model'=>$model,
			'static'=>$static_view,
			'undeletableMessage'=>'Домен используется'
		]) ?>
	</h1>
	<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'fingerprint']) ?>

</div>
