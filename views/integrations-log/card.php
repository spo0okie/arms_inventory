<?php

/**
 * Карточка записи журнала интеграций (view). Реквизиты действия +
 * связанные шаги составного действия (parent/children через parent_id).
 *
 * @var yii\web\View $this
 * @var app\models\IntegrationsLog $model
 * @var bool $static_view
 */

use app\helpers\StringHelper;
use yii\helpers\Html;

if (!isset($static_view)) $static_view = false;

$resultBadge = static function ($result) {
	$map = ['ok' => 'bg-success', 'error' => 'bg-danger', 'run' => 'bg-secondary'];
	return Html::tag('span', Html::encode($result), ['class' => 'badge '.($map[$result] ?? 'bg-secondary')]);
};

//строка одной записи-ссылки (для parent/children)
$logLink = static function ($log) {
	return Html::a(
		'#'.$log->id.' '.Html::encode($log->providerTitle().' / '.$log->action),
		['/integrations-log/view', 'id' => $log->id]
	);
};

$objectRoute = $model->objectRoute();

?>
<div class="integrations-log-card">
	<h1>Действие интеграции #<?= $model->id ?></h1>

	<table class="table table-sm w-auto">
		<tr><td class="text-secondary pe-3">Когда</td><td><?= Html::encode($model->created_at) ?></td></tr>
		<tr><td class="text-secondary pe-3">Результат</td><td><?= $resultBadge($model->result) ?></td></tr>
		<tr><td class="text-secondary pe-3">Интеграция</td><td><?= Html::encode($model->providerTitle()) ?> <span class="text-secondary">(<?= Html::encode($model->provider) ?>)</span></td></tr>
		<tr><td class="text-secondary pe-3">Действие</td><td><?= Html::encode($model->action) ?></td></tr>
		<tr>
			<td class="text-secondary pe-3">Инициатор</td>
			<td><?= is_object($model->user) ? $model->user->renderItem($this) : '<span class="text-secondary">— (консоль/система)</span>' ?></td>
		</tr>
		<?php if ($model->ext_login) { ?>
			<tr><td class="text-secondary pe-3">Внешняя учётка</td><td><?= Html::encode($model->ext_login) ?></td></tr>
		<?php } ?>
		<tr>
			<td class="text-secondary pe-3">Объект</td>
			<td><?php
				if ($objectRoute) {
					echo Html::a(Html::encode(StringHelper::className($model->class).' #'.$model->object_id), $objectRoute);
				} else {
					echo '<span class="text-secondary">— (без объекта)</span>';
				}
			?></td>
		</tr>
		<?php if ($model->params) { ?>
			<tr><td class="text-secondary pe-3">Параметры</td><td><code><?= Html::encode($model->params) ?></code></td></tr>
		<?php } ?>
		<?php if ($model->message !== null && $model->message !== '') { ?>
			<tr><td class="text-secondary pe-3">Сообщение</td><td><?= Html::encode($model->message) ?></td></tr>
		<?php } ?>
	</table>

	<?php
	//составное действие: показываем инициатора (если это вложенный шаг)
	//и вложенные шаги (если это инициатор)
	if (is_object($model->parent)) { ?>
		<div class="mt-2"><span class="text-secondary">Вызвано из:</span> <?= $logLink($model->parent) ?></div>
	<?php }
	$children = $model->children;
	if (is_array($children) && count($children)) { ?>
		<div class="mt-2">
			<span class="text-secondary">Вложенные шаги:</span>
			<ul class="mb-0">
				<?php foreach ($children as $child) { ?>
					<li><?= $logLink($child) ?> — <?= $resultBadge($child->result) ?></li>
				<?php } ?>
			</ul>
		</div>
	<?php } ?>
</div>
