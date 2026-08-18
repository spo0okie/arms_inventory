<?php
/**
 * Панель «Постановка на мониторинг»: в карточке — только бейдж вердикта
 * explain-режима синхронизации arms.zabbix; клик по бейджу открывает
 * модальное окно с журналом совпавших правил, полный лог (все проверенные
 * правила и на каком условии срезались) — в сворачиваемом «подробно»
 * внутри окна.
 *
 * Модалка — статичная разметка Bootstrap 5 прямо в HTML панели: кэш
 * панелей общий на инстанс, поэтому содержимое одинаково для всех
 * зрителей — персонализации здесь нет.
 */

use app\components\integrations\providers\ZabbixSyncProvider;
use yii\helpers\Html;

/* @var $report array ответ explain.php: verdict/errors/status/sets/actions */
/* @var $model \app\models\base\ArmsModel */
/* @var $provider ZabbixSyncProvider */
/* @var $compact bool панель рисуется во вложенном списке - нужно плотнее */

$verdict = $report['verdict'] ?? 'skip';
[$badgeClass, $badgeText] = ZabbixSyncProvider::VERDICTS[$verdict]
	?? ['bg-secondary', $verdict];

//приостановленный мониторинг важнее «зелёного» вердикта: узел в Zabbix
//будет, но выключен (архив/списание и т.п.)
if (($report['status'] ?? null) === 1 && in_array($verdict, ['add', 'monitored'])) {
	$badgeClass = 'bg-warning text-dark';
	$badgeText .= ', но приостановлен';
}

//метка набора/правила: имя из rules.priv.php, если задано, иначе номер
$setLabel = static function (array $set): string {
	if (is_string($set['index'] ?? null)) return $set['index'];
	return ($set['desc'] ?? null) ?: 'набор №'.($set['index'] ?? '?');
};
$ruleLabel = static function (array $rule): string {
	return ($rule['desc'] ?? null) ?: 'правило №'.($rule['index'] ?? '?');
};

//совпавшие правила по наборам - краткий журнал
$matched = [];
foreach ($report['sets'] ?? [] as $set) {
	if (is_null($set['matched'] ?? null)) continue;
	foreach ($set['rules'] ?? [] as $rule) {
		if (($rule['index'] ?? null) === $set['matched'] && ($rule['matched'] ?? false)) {
			$matched[] = ['set' => $setLabel($set), 'rule' => $ruleLabel($rule)];
		}
	}
}

//ключевые итоговые действия для краткой сводки
$actions = $report['actions'] ?? [];
$summary = [];
if (!empty($actions['templates'])) $summary['Шаблоны'] = implode(', ', (array)$actions['templates']);
if (!empty($actions['groups'])) $summary['Группы'] = implode(', ', (array)$actions['groups']);

$uid = ($report['host']['class'] ?? 'x').'-'.($report['host']['id'] ?? '0');
$modalId = 'zabbix-sync-modal-'.$uid;
$logId = 'zabbix-sync-log-'.$uid;

//во вложенных списках (компакт) - только бейдж без журнала
if ($compact) {
	echo '<span class="badge '.$badgeClass.'">'.Html::encode($badgeText).'</span>';
	return;
}
?>
<a href="#" class="badge text-decoration-none <?= $badgeClass ?>"
   data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>"
   title="Показать журнал правил синхронизации"><?= Html::encode($badgeText) ?> <i class="fas fa-circle-info"></i></a>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">
					<?= Html::encode($provider->getTitle()) ?>:
					<?= Html::encode($report['host']['name'] ?? '') ?>
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
			</div>
			<div class="modal-body">
				<div>
					<span class="badge <?= $badgeClass ?>"><?= Html::encode($badgeText) ?></span>
					<?php foreach ($report['errors'] ?? [] as $error) { ?>
						<span class="text-secondary"><?= Html::encode($error) ?></span>
					<?php } ?>
				</div>

				<?php foreach ($summary as $title => $value) { ?>
					<div><small><span class="text-secondary"><?= Html::encode($title) ?>:</span> <?= Html::encode($value) ?></small></div>
				<?php } ?>

				<?php if (count($matched)) { ?>
					<div class="mt-2">
						<?php foreach ($matched as $hit) { ?>
							<div><small>
								<span class="text-success">&#10003;</span>
								<span class="text-secondary"><?= Html::encode($hit['set']) ?>:</span>
								<?= Html::encode($hit['rule']) ?>
							</small></div>
						<?php } ?>
					</div>
				<?php } ?>

				<div class="mt-2">
					<small>
						<a data-bs-toggle="collapse" href="#<?= $logId ?>" role="button" class="text-decoration-none">подробно</a>
					</small>
				</div>

				<div class="collapse mt-2" id="<?= $logId ?>">
					<?php foreach ($report['sets'] ?? [] as $set) { ?>
						<div class="mt-1"><small><b><?= Html::encode($setLabel($set)) ?></b></small></div>
						<?php foreach ($set['rules'] ?? [] as $rule) { ?>
							<div class="ps-3"><small>
								<?php if ($rule['matched'] ?? false) { ?>
									<span class="text-success">&#10003;</span> <?= Html::encode($ruleLabel($rule)) ?>
									<span class="text-secondary">[<?= Html::encode($rule['conditions'] ?? '') ?>]</span>
								<?php } else { ?>
									<span class="text-secondary">&#10007; <?= Html::encode($ruleLabel($rule)) ?>
									[<?= Html::encode($rule['conditions'] ?? '') ?>]
									— не прошло условие '<?= Html::encode($rule['failedOn'] ?? '?') ?>'</span>
								<?php } ?>
							</small></div>
						<?php } ?>
						<?php if (is_null($set['matched'] ?? null)) { ?>
							<div class="ps-3"><small class="text-secondary">ни одно правило не совпало</small></div>
						<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>
