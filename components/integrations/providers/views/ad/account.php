<?php
/**
 * Панель «ActiveDirectory» карточки сотрудника: рендер нормализованных
 * атрибутов учётки из AdUserProvider::fetchAccount()
 */

use yii\helpers\Html;

/* @var $account array|null см. AdUserProvider::fetchAccount() */
/* @var $model \app\models\Users */

if (is_null($account)) {
	echo '<span class="text-secondary opacity-75">учётка '
		.Html::encode($model->Login).' не найдена в AD</span>';
	return;
}

$formatter = Yii::$app->formatter;
$date = static function ($value) use ($formatter) {
	if ($value === 'never') return 'никогда';
	if (empty($value)) return '-';
	return $formatter->asDatetime($value, 'php:d.m.Y H:i');
};

//статус: отключена > заблокирована > пароль просрочен > активна
//(блокировка и просроченность - из вычисляемого AD признака, см. LdapService)
if (!$account['enabled']) {
	$badge = ['bg-secondary', 'отключена'];
} elseif (!empty($account['locked'])) {
	$badge = ['bg-danger', 'заблокирована'];
} elseif (!empty($account['password_expired'])) {
	$badge = ['bg-warning text-dark', 'пароль просрочен'];
} else {
	$badge = ['bg-success', 'активна'];
}

?>
<div>
	Учетная запись: <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
	<?php if ($account['must_change_password']) { ?>
		<span class="badge bg-warning text-dark">требуется смена пароля</span>
	<?php } ?>
</div>
<div class="mt-1"><small class="text-secondary" title="distinguishedName"><?= Html::encode($account['dn']) ?></small></div>
<div class="mt-1"><small>
	<span class="text-secondary">Пароль изменён:</span> <?= $date($account['password_last_set']) ?>
	<span class="text-secondary ms-2">истекает:</span> <?= $date($account['password_expires']) ?>
</small></div>
<div><small>
	<span class="text-secondary">Последний вход:</span> <?= $date($account['last_logon']) ?>
	<?php if ($account['account_expires'] !== 'never') { ?>
		<span class="text-secondary ms-2">учётка истекает:</span> <?= $date($account['account_expires']) ?>
	<?php } ?>
</small></div>
