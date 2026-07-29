<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use yii\helpers\Html;

/**
 * Карточка уведомления для view и tooltip: реквизиты очереди + письмо
 * в том виде, в каком оно уйдёт получателю.
 *
 * @var yii\web\View $this
 * @var app\models\Notifications $model
 * @var bool $static_view
 */

if (!isset($static_view)) $static_view = false;
?>

<div class="notification-card">

	<h1>
		<?= LinkObjectWidget::widget([
			'model' => $model,
			'static' => $static_view,
			'confirmMessage' => 'Действительно удалить это уведомление?',
		]) ?>
	</h1>

	<?php
	//строки «подпись: значение»; пустые (напр. sent_at у неотправленного) отсекаются
	$rows = [];
	foreach (['user_id', 'event_key', 'created_at', 'sent_at', 'attempts', 'last_error'] as $field) {
		$rows[] = ModelFieldWidget::renderFieldRow(
			$model,
			$field,
			['item_options' => ['static_view' => $static_view]]
		);
	}
	echo Html::tag('p', implode('<br />', array_filter($rows)));
	?>

	<?php if (strlen((string)$model->body)): ?>
		<div class="card">
			<div class="card-header"><?= Html::encode($model->getAttributeLabel('body')) ?></div>
			<div class="card-body">
				<?php
				//письмо и есть HTML - показываем как увидит получатель
				echo $model->body;
				?>
			</div>
		</div>
	<?php endif; ?>

</div>
