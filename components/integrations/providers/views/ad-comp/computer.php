<?php
/**
 * Панель «ActiveDirectory» карточки ОС: где учётка компьютера лежит в
 * дереве AD и в каких группах состоит.
 * Данные нормализованы в LdapService::computerInfo().
 */

use yii\helpers\Html;

/* @var $computer array|null см. LdapService::computerInfo() */
/* @var $model \app\models\Comps */
/* @var $compact bool панель рисуется во вложенном списке - нужно плотнее */

if (is_null($computer)) {
	echo '<span class="text-secondary opacity-75">учётка компьютера '
		.Html::encode($model->name).' не найдена в AD</span>';
	return;
}

$pad = $compact ? 'pe-2' : 'pe-4';

//путь в дереве: домен › контейнеры сверху вниз (полный DN - в подсказке)
$path = array_merge(
	array_filter([$computer['domain'] ?? '']),
	$computer['path'] ?? []
);

?>
<div class="d-flex align-items-center">
	<div class="<?= $pad ?>">
		<?php if (!$computer['enabled']) { ?>
			<span class="badge bg-secondary">учётка отключена</span>
		<?php } ?>
		<span class="text-secondary" title="distinguishedName: <?= Html::encode($computer['dn']) ?>">
			<?= implode(' <span class="text-secondary">&rsaquo;</span> ', array_map('yii\helpers\Html::encode', $path)) ?>
		</span>
	</div>
</div>
<div class="mt-1">
	<?php if (count($computer['groups'])) { ?>
		<span class="text-secondary">Группы:</span>
		<?php foreach ($computer['groups'] as $group) { ?>
			<span class="badge bg-light text-dark border" title="<?= Html::encode($group['dn']) ?>"><?= Html::encode($group['name']) ?></span>
		<?php } ?>
	<?php } else { ?>
		<span class="text-secondary">Групп нет</span>
		<span class="text-secondary opacity-75" title="Первичная группа (обычно «Компьютеры домена») в memberOf не входит">
			<i class="fas fa-question-circle"></i>
		</span>
	<?php } ?>
</div>
