<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$static_view = $static_view ?? false;

?>

<h1>
	<?= LinkObjectWidget::widget([
		'model' => $model,
		'static' => $static_view,
		'hideUndeletable' => false,
	]) ?>
</h1>
<?= ModelFieldWidget::renderFieldValue($model, 'comment') ?>
