<?php
/**
 * Путь объекта по дереву AD — общий кусок панелей учётки сотрудника
 * (`ad`) и учётки компьютера (`ad-comp`): один и тот же DN должен
 * выглядеть одинаково в обеих карточках.
 *
 * Данные нормализованы в LdapService::placement().
 * Подключается по алиасу:
 * `$this->render('@app/components/integrations/providers/views/ad-common/dn-path', [...])`
 */

use yii\helpers\Html;

/* @var $entry array см. LdapService::placement(): dn, path, domain */

//домен › контейнеры сверху вниз; полный DN - в подсказке
$path = array_merge(
	array_filter([$entry['domain'] ?? '']),
	$entry['path'] ?? []
);

//путь не разобрался (пустой DN) - показываем DN как есть, чем ничего
if (!count($path)) $path = array_filter([$entry['dn'] ?? '']);

?>
<small class="text-secondary" title="distinguishedName: <?= Html::encode($entry['dn'] ?? '') ?>">
	<?= implode(' &rsaquo; ', array_map('yii\helpers\Html::encode', $path)) ?>
</small>
