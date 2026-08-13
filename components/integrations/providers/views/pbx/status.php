<?php
/**
 * Рендер панели телефонии: ответ GET /api/v1/subscribers/status
 * приложения ast22-phones (см. там plans/subscriber-status-api.md).
 * Подключается конфигом провайдера pbx (HttpTemplateProvider, 'panel.template').
 *
 * Показывает: прогрессивный бейдж состояния + 4 «лампочки» (в БД / в
 * Asterisk / зарегистрирован / онлайн), IP и модель зарегистрированного
 * телефона, кнопку перехода в Web-UI абонента, настроенные переадресации.
 */

use yii\helpers\Html;

/* @var $data array декодированный JSON-ответ {success, data:{subscriber, status, call_duplications}} */
/* @var $model \app\models\Techs */
/* @var $provider \app\components\integrations\providers\HttpTemplateProvider */

$subscriber = $data['data']['subscriber'] ?? [];
$status = $data['data']['status'] ?? [];
$duplications = $data['data']['call_duplications'] ?? [];

if (empty($data['success']) || empty($status)) {
	echo '<span class="text-secondary opacity-75">номер '
		.Html::encode($model->phone).' не найден в телефонии</span>';
	return;
}

$configured = !empty($status['configured']); //есть в БД телефонии
$loaded = !empty($status['loaded']);         //Asterisk загрузил endpoint
$registered = !empty($status['registered']); //телефон зарегистрирован
$online = !empty($status['online']);         //достижим (qualify)

//прогрессивный бейдж: самая дальняя достигнутая ступень
if (!$configured) {
	$badge = ['bg-secondary', 'не в БД'];
} elseif (!$loaded) {
	$badge = ['bg-secondary', 'не загружен в Asterisk'];
} elseif (!$registered) {
	$badge = ['bg-danger', 'не зарегистрирован'];
} elseif (!$online) {
	$badge = ['bg-warning text-dark', 'зарегистрирован, недоступен'];
} else {
	$badge = ['bg-success', 'онлайн'];
}

//4 «лампочки» статуса: горит зелёным = ступень достигнута
$lamp = static function (bool $on, string $title): string {
	$color = $on ? '#198754' : '#ced4da';
	return '<span title="'.Html::encode($title).'" qtip_ttip="'.Html::encode($title).'"'
		.' style="display:inline-block;width:11px;height:11px;border-radius:50%;'
		.'background:'.$color.';margin-right:3px;vertical-align:middle"></span>';
};

//IP и модель — из первого зарегистрированного контакта (sip:NNN@IP:port)
$ip = null;
$userAgent = null;
foreach ($status['contacts'] ?? [] as $contact) {
	if (empty($contact['uri'])) continue;
	if (preg_match('~@([^:;]+)~', $contact['uri'], $m)) $ip = $m[1];
	if (!empty($contact['user_agent'])) $userAgent = $contact['user_agent'];
	break;
}

//ссылка на абонента в Web-UI телефонии. База — из конфига 'web'
//(браузерный URL); если не задан, берём хост из 'request' (частый случай:
//браузер и backend ходят на один адрес). Кнопка появляется без доп. конфига.
$web = $provider->config['web'] ?? null;
if (!$web && !empty($provider->config['request'])
	//берём scheme://host:port из request регуляркой: parse_url спотыкается
	//о плейсхолдеры {binding}, ещё не подставленные в шаблоне request
	&& preg_match('~^(https?://[^/]+)~i', $provider->config['request'], $m)) {
	$web = $m[1];
}
$subId = $subscriber['id'] ?? null;
$webUrl = null;
if ($web && $subId !== null) {
	$path = $provider->config['webSubscriber'] ?? '/subscriber/view?id={id}';
	$webUrl = rtrim($web, '/').str_replace('{id}', rawurlencode((string)$subId), $path);
}

?>
<div class="d-flex justify-content-between align-items-center">
	<span>
		<span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
		<span class="ms-1">
			<?= $lamp($configured, 'В базе телефонии') ?>
			<?= $lamp($loaded, 'Загружен в Asterisk') ?>
			<?= $lamp($registered, 'Зарегистрирован') ?>
			<?= $lamp($online, 'Онлайн (qualify)') ?>
		</span>
	</span>
	<?php if ($webUrl) { ?>
		<small><?= Html::a('в телефонии <i class="fas fa-external-link-alt"></i>', $webUrl,
			['target' => '_blank', 'rel' => 'noopener']) ?></small>
	<?php } ?>
</div>
<?php if (!empty($status['error'])) { ?>
	<div class="mt-1"><small class="text-danger"><?= Html::encode($status['error']) ?></small></div>
<?php } ?>
<?php if ($ip || $userAgent) { ?>
	<div class="mt-1"><small>
		<?php if ($ip) { ?>
			<span class="text-secondary">IP:</span> <?= Html::encode($ip) ?>
		<?php } ?>
		<?php if ($userAgent) { ?>
			<span class="text-secondary ms-2">модель:</span> <?= Html::encode($userAgent) ?>
		<?php } ?>
	</small></div>
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
