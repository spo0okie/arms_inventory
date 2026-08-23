<?php
/**
 * Улики пустого ответа сервиса (arms.macsearch, раздел «Диагностика»).
 *
 * Пустой результат снаружи выглядит одинаково и когда адреса действительно
 * нет, и когда прошивка печатает незнакомый формат, и когда учётка не
 * прошла. Сервис в таких случаях присылает `diagnostics`; здесь они
 * показываются свёрнутым блоком — чтобы не мешать обычной работе, но
 * чтобы было что скопировать в задачу, когда результат подозрительный.
 */

use yii\helpers\Html;

/* @var $diagnostics array записи сервиса: цель, команды, вердикт, сырой ответ */
/* @var $switches \app\models\Techs[] опрошенные коммутаторы (id => модель) */

if (!count($diagnostics)) return;

//текст для копирования: одна цель - один абзац, всё, что прислал сервис
$plain = [];
foreach ($diagnostics as $notes) {
	$lines = [($notes['host'] ?? '?').' ('.($notes['mode'] ?? '?').'): '
		.($notes['verdict'] ?? 'без вердикта')];
	if (!empty($notes['commands'])) $lines[] = 'команды: '.implode(' | ', $notes['commands']);
	$lines[] = 'разобрано строк: '.(int)($notes['matched'] ?? 0)
		.', символов в ответе: '.(int)($notes['output_chars'] ?? 0)
		.(empty($notes['zero_marker']) ? '' : ', коммутатор сообщила «0 записей»');
	if (!empty($notes['dropped_sample'])) {
		$lines[] = 'не разобрано: '.implode(' / ', $notes['dropped_sample']);
	}
	if (!empty($notes['output_head'])) $lines[] = 'ответ: '.$notes['output_head'];
	$plain[] = implode("\n", $lines);
}

?>
<details class="text-secondary small mb-1">
	<summary>почему пусто (диагностика сервиса)</summary>
	<?php foreach ($diagnostics as $index => $notes) { ?>
		<div class="mt-1">
			<?php $tech = $switches[$notes['target'] ?? null] ?? null; ?>
			<b><?= Html::encode(is_object($tech) ? $tech->name : ($notes['host'] ?? '')) ?></b>
			— <?= Html::encode($notes['verdict'] ?? 'без вердикта') ?>
		</div>
	<?php } ?>
	<?php /* сырой текст целиком: его и просят приложить, когда разбираются,
	       почему опрос вернул пустоту */ ?>
	<pre class="mt-1 mb-0 p-1 small text-secondary" style="white-space:pre-wrap"><?=
		Html::encode(implode("\n\n", $plain)) ?></pre>
</details>
