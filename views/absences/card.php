<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use yii\helpers\Html;

/**
 * Карточка отсутствия для view и tooltip.
 *
 * @var yii\web\View $this
 * @var app\models\Absences $model
 * @var bool $static_view
 */

if (!isset($static_view)) $static_view = false;
?>

<div class="absences-card">

	<h1>
		<?= LinkObjectWidget::widget([
			'model' => $model,
			'static' => $static_view,
			'confirmMessage' => 'Действительно удалить это отсутствие?',
		]) ?>
	</h1>

	<?php
	//строки «подпись: значение»; пустые (напр. external_id ручного ввода) отсекаются
	$rows = [];
	foreach (['user_id', 'type', 'date_from', 'date_to', 'source', 'external_id', 'comment'] as $field) {
		$rows[] = ModelFieldWidget::renderFieldRow(
			$model,
			$field,
			['item_options' => ['static_view' => $static_view]]
		);
	}
	echo Html::tag('p', implode('<br />', array_filter($rows)));
	?>

</div>
