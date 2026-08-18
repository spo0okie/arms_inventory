<?php
/**
 * Панель «ActiveDirectory» карточки сотрудника: рендер нормализованных
 * атрибутов учётки из AdUserProvider::fetchAccount() + кнопки действий
 * по живому состоянию учётки (сброс пароля - у найденной, создание - у
 * ненайденной, восстановление - у уволенной). Кнопки - L0-ссылки,
 * одинаковые для всех (кэш панелей общий), доступ к действию проверяет
 * сервер при открытии формы.
 */

use yii\helpers\Html;

/* @var $account array|null см. AdUserProvider::fetchAccount() */
/* @var $model \app\models\Users */
/* @var $dismissed bool учётка отключена и лежит в контейнере уволенных */
/* @var $resetUrl string|null URL формы сброса пароля (null = действие недоступно) */
/* @var $createUrl string|null URL формы создания учётки (null = недоступно) */
/* @var $restoreUrl string|null URL формы восстановления учётки (null = недоступно) */
/* @var $compact bool вложенный список - без кнопок действий */

if (is_null($account)) {
	//учётки нет: показываем причину и (не в compact) кнопку создания
	echo '<span class="text-secondary opacity-75">'
		.(empty($model->Login)
			? 'логин AD не задан - учётной записи нет'
			: 'учётка '.Html::encode($model->Login).' не найдена в AD')
		.'</span>';
	if (!$compact && !empty($createUrl)) {
		echo '<div class="mt-2">'.Html::a('<i class="fas fa-user-plus"></i> Создать учётную запись',
			$createUrl, ['class' => 'btn btn-sm btn-secondary open-in-modal-form']).'</div>';
	}
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
	<?php if ($dismissed) { ?>
		<span class="badge bg-dark">в уволенных</span>
	<?php } ?>
	<?php if ($account['must_change_password']) { ?>
		<span class="badge bg-warning text-dark">требуется смена пароля</span>
	<?php } ?>
</div>
<div class="mt-1"><?= $this->render('@app/components/integrations/providers/views/ad-common/dn-path',
	['entry' => $account]) ?></div>
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
<?php if (!$compact && !empty($resetUrl)) { ?>
<div class="mt-2"><?= Html::a('<i class="fas fa-key"></i> Сбросить пароль', $resetUrl,
	['class' => 'btn btn-sm btn-secondary open-in-modal-form']) ?></div>
<?php } ?>
<?php if (!$compact && !empty($restoreUrl)) { ?>
<div class="mt-2"><?= Html::a('<i class="fas fa-user-check"></i> Восстановить учётную запись', $restoreUrl,
	['class' => 'btn btn-sm btn-secondary open-in-modal-form']) ?></div>
<?php } ?>
