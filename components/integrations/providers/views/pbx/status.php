<?php
/**
 * Рендер панели телефонии: ответ GET /api/v1/subscribers/status
 * приложения ast22-phones (см. там plans/subscriber-status-api.md).
 * Подключается конфигом провайдера pbx (HttpTemplateProvider, 'panel.template').
 */

use yii\helpers\Html;

/* @var $data array декодированный JSON-ответ {success, data:{subscriber, status, call_duplications}} */
/* @var $model \app\models\Techs */
/* @var $provider \app\components\integrations\providers\HttpTemplateProvider */

$status = $data['data']['status'] ?? [];
$duplications = $data['data']['call_duplications'] ?? [];

if (empty($data['success']) || empty($status)) {
	echo '<span class="text-secondary opacity-75">номер '
		.Html::encode($model->phone).' не найден в телефонии</span>';
	return;
}

//прогрессивный статус: не настроен → не зарегистрирован → offline → online
if (empty($status['configured'])) {
	$badge = ['bg-secondary', 'не настроен'];
} elseif (empty($status['registered'])) {
	$badge = ['bg-danger', 'не зарегистрирован'];
} elseif (empty($status['online'])) {
	$badge = ['bg-warning text-dark', 'зарегистрирован, недоступен'];
} else {
	$badge = ['bg-success', 'онлайн'];
}

?>
<div>
	<span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
	<?php if (!empty($status['device_state'])) { ?>
		<small class="text-secondary ms-1"><?= Html::encode($status['device_state']) ?></small>
	<?php } ?>
	<?php if (!empty($status['error'])) { ?>
		<small class="text-danger ms-1"><?= Html::encode($status['error']) ?></small>
	<?php } ?>
</div>
<?php foreach ($status['contacts'] ?? [] as $contact) { ?>
	<div class="mt-1">
		<small>
			<?= Html::encode($contact['uri'] ?? '') ?>
			<?php if (!empty($contact['user_agent'])) { ?>
				<span class="text-secondary">(<?= Html::encode($contact['user_agent']) ?>)</span>
			<?php } ?>
			<?php if (isset($contact['rtt_ms'])) { ?>
				<span class="text-secondary">rtt <?= Html::encode((string)$contact['rtt_ms']) ?> мс</span>
			<?php } ?>
		</small>
	</div>
<?php } ?>
<?php if (count($duplications)) { ?>
	<div class="mt-1">
		<small class="text-secondary">Дублирование вызова:</small>
		<?php foreach ($duplications as $duplication) { ?>
			<small class="text-nowrap">
				→ <?= Html::encode($duplication['dubler_number'] ?? '') ?><?php
				if (!empty($duplication['delay_seconds'])) {
					?> <span class="text-secondary">через <?= (int)$duplication['delay_seconds'] ?> с</span><?php
				}
				if (!empty($duplication['schedule'])) {
					?> <span class="text-secondary">(<?= Html::encode($duplication['schedule']) ?>)</span><?php
				}
			?></small>
		<?php } ?>
	</div>
<?php } ?>
