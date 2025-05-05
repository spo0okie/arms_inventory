<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Domains */
/* @var $static_view boolean */
?>

<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'static'=>$static_view,
		'undeletableMessage'=>'Домен используется'
	]) ?>
</h1>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'fqdn']) ?>
<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>
