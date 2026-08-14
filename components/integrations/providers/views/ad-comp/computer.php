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

?>
<?php if (!$computer['enabled']) { ?>
	<div><span class="badge bg-secondary">учётка отключена</span></div>
<?php } ?>
<div><?= $this->render('@app/components/integrations/providers/views/ad-common/dn-path',
	['entry' => $computer]) ?></div>
<div class="<?= $compact ? 'mt-0' : 'mt-1' ?>"><small>
	<?php if (count($computer['groups'])) { ?>
		<span class="text-secondary">Группы:</span>
		<?php foreach ($computer['groups'] as $group) { ?>
			<span class="badge bg-light text-dark border" title="<?= Html::encode($group['dn']) ?>"><?= Html::encode($group['name']) ?></span>
		<?php } ?>
	<?php } else { ?>
		<span class="text-secondary" title="Первичная группа («Компьютеры домена») и вложенные группы в memberOf не входят">
			групп нет
		</span>
	<?php } ?>
</small></div>
