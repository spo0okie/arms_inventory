<?php
/**
 * Результат опроса коммутатора: та же таблица портов, что и в карточке
 * ({@see views/techs/_ports-table.php}), но с наложенным слоем находок.
 *
 * Опрос не приносит новую сущность — он подтверждает или опровергает то, что
 * записано, поэтому таблица одна, а этот вид её лишь обогащает и добавляет
 * снизу свёрнутые сырые данные с коммутатора.
 */

use app\models\Techs;
use yii\helpers\Html;

/* @var $ports array порты {@see MacSearchProvider::switchPorts()} */
/* @var $data array|null ответ сервиса (status/targets/errors/rows) */
/* @var $error string|null сервис не ответил */
/* @var $refreshUrl string|null URL самоперезапроса панели, пока идёт опрос */
/* @var $tech Techs коммутатор, чью карточку открыли */
/* @var $provider \app\components\integrations\providers\MacSearchProvider */
/* @var $stack \app\models\Techs[] члены стека (пусто - коммутатор одиночный) */
/* @var $foreignNames string[] имена портов, объявленные у соседей по стеку */
/* @var $identity array визитка: sysname/sysdescr/location/base_mac/units
 *      (пусто - устройство о себе не рассказало) */
/* @var $scanOk bool опрос завершился полным успехом (слой сверки валиден) */

$status = $data['status'] ?? null;

//неопрошенная коммутатор тут ровно одна - это он сам
$failure = ($data['errors'] ?? [])[0] ?? null;

//что помешало опросу (в порядке убывания «это вообще не наши данные»)
$trouble = null;
if ($error) $trouble = 'опрос не выполнен: '.$error;
elseif ($status === 'error') $trouble = 'опрос не выполнен: '.($data['error'] ?? 'ошибка сервиса');
elseif ($failure) $trouble = 'коммутатор не опрошен: '.($failure['error'] ?? 'причина не указана');

?>
<?php if (!empty($stack)) { ?>
	<?php /* стек выводится, а не хранится: общий IP на одной площадке. Человеку
	       важно видеть, что порты тут - доля одного члена, а остальные - у
	       соседей; карточки соседей - ссылками */ ?>
	<div class="text-secondary small mb-1">
		стек (общий IP): <?php
		$links = [];
		foreach ($stack as $member) {
			$links[] = $member->id === $tech->id
				? '<b>'.Html::encode($member->name).'</b>'
				: \app\components\widgets\page\ModelWidget::widget(['model' => $member,
					'options' => ['static_view' => true]]);
		}
		echo implode(', ', $links); ?>
		— показаны порты этого члена<?= $stack[0]->id === $tech->id
			? ' и те, что не удалось отнести ни к кому (он первый по id)' : '' ?>
	</div>
<?php } ?>

<?php if ($status === 'pending') { ?>
	<div class="text-secondary mb-1">
		<span class="spinner-border spinner-border-sm" role="status"></span>
		&mdash; идёт опрос коммутатора<?= $refreshUrl ? ''
			: ' — в отведённое время не уложился, обновите панель для нового опроса' ?>
	</div>
<?php } elseif ($trouble) { ?>
	<div class="text-secondary opacity-75 mb-1"<?= empty($failure['detail']) ? ''
		: ' qtip_ttip="'.Html::encode($failure['detail']).'"' ?>><?= Html::encode($trouble) ?></div>
<?php } ?>

<?php if (!empty($identity)) { ?>
	<?php /* визитка: то, что устройство говорит о себе само, - как есть, без
	       выводов. Сверять с карточкой (серийник, MAC, имя) будет человек:
	       ему для этого достаточно видеть обе стороны рядом */ ?>
	<div class="small mb-2">
		<span class="text-secondary">коммутатор о себе:</span>
		<table class="table table-sm table-borderless w-auto mb-0 small">
			<?php if (!empty($identity['sysname'])) { ?>
				<tr><td class="text-secondary pe-2">имя (sysName)</td>
					<td><?= Html::encode($identity['sysname']) ?></td></tr>
			<?php } ?>
			<?php if (!empty($identity['base_mac'])) { ?>
				<tr><td class="text-secondary pe-2">базовый MAC</td>
					<td><?= Html::encode($identity['base_mac']) ?></td></tr>
			<?php } ?>
			<?php foreach ($identity['units'] ?? [] as $unit) {
				if (empty($unit['serial'])) continue;
				$name = trim((string)($unit['name'] ?? '')) ?: (string)($unit['class'] ?? '');
				$extra = array_filter([$unit['model'] ?? '',
					empty($unit['sw']) ? '' : 'ПО '.$unit['sw']]); ?>
				<tr><td class="text-secondary pe-2">серийный номер<?=
					$name !== '' ? ' '.Html::encode($name) : '' ?></td>
					<td><?= Html::encode($unit['serial'])
						.(count($extra) ? ' <span class="text-secondary">('
							.Html::encode(implode(', ', $extra)).')</span>' : '') ?></td></tr>
			<?php } ?>
			<?php if (!empty($identity['sysdescr'])) { ?>
				<tr><td class="text-secondary pe-2">о себе (sysDescr)</td>
					<td style="max-width:40em; overflow-wrap:anywhere"><?= Html::encode($identity['sysdescr']) ?></td></tr>
			<?php } ?>
			<?php if (!empty($identity['location'])) { ?>
				<tr><td class="text-secondary pe-2">расположение (sysLocation)</td>
					<td><?= Html::encode($identity['location']) ?></td></tr>
			<?php } ?>
		</table>
	</div>
<?php } elseif ($status === 'done' && !$trouble && empty($data['ports'])) { ?>
	<?php /* опрос прошёл, а SNMP-данных нет совсем (ни визитки, ни паспорта) -
	       почти всегда это не «устройство молчит», а сервису не задан
	       community для этой сети. Молчать нельзя: два дня искали, почему
	       «карточки нет», хотя ответ - одна строка конфига */ ?>
	<div class="text-secondary opacity-75 small mb-2">
		визитка и паспорт портов не получены — они снимаются по SNMP:
		проверьте, задан ли community для этой сети в настройках сервиса
		(credentials, журнал сервиса называет причину)
	</div>
<?php } ?>

<?php /* путь алиасом, а не '/techs/...': вид рендерится не из контроллера
       techs, а из proxy интеграций - относительный путь там не резолвится */ ?>
<?php /* без успешного опроса ($ports = null) таблица рисуется ровно как в
       карточке: вердикты о записанных связях по сбойным или недособранным
       данным не выносятся */ ?>
<?= $this->render('@app/views/techs/_ports-table', ['model' => $tech, 'ports' => $ports,
	//паспорт передаём отдельно: в таблице порты идут в объявленном порядке, а
	//«назвать порты как на коммутаторе» должно взять и порядок коммутатора тоже
	'passport' => $scanOk ? ($data['ports'] ?? []) : null,
	'transitFrom' => $provider->transitFrom(),
	'foreignNames' => $foreignNames ?? [],
	'scanStamp' => $scanOk ? $provider->scanStamp($data) : null]) ?>

<?php if (!$trouble && $status !== 'pending') { ?>
	<?php /* сырые данные - свёрнутыми: на основной таблице они мозолят глаза,
	       но если человек полез за подробностями, прятать их незачем. Тут же
	       и адреса транзитных портов, которых в основной таблице нет */ ?>
	<details class="text-secondary small mb-2">
		<summary>показать данные с коммутатора</summary>
		<?= $this->render('_raw', ['data' => $data]) ?>
	</details>
<?php } ?>

<?php /* что коммутатор отдаёт по CLI и по SNMP (команды, права, LLDP) - в
       отличие от блока выше, диагностика есть и у сбойного опроса: SSH умер,
       а SNMP жив (или наоборот) - отсюда это видно */ ?>
<?php $capabilitiesHtml = is_array($data)
	? $provider->renderCapabilities($data['capabilities'] ?? [],
		count($stack ?? []) ? array_combine(array_map(fn($member) => $member->id, $stack), $stack)
			: [$tech->id => $tech])
	: ''; ?>
<?php if ($capabilitiesHtml !== '') { ?>
	<details class="text-secondary small mb-2">
		<summary>диагностика опроса: что отдаёт коммутатор (CLI/SNMP)</summary>
		<?= $capabilitiesHtml ?>
	</details>
<?php } ?>

<?= $this->render('_diagnostics', [
	'diagnostics' => $data['diagnostics'] ?? [],
	'switches' => [$tech->id => $tech],
]) ?>

<?php if ($refreshUrl) { ?>
	<?php /* скрипт живёт ВНУТРИ подменяемого контейнера: ответ заменит его
	       целиком вместе со скриптом, поэтому опрос продолжается сам собой */ ?>
	<script>
		setTimeout(function () {
			$.get(<?= json_encode($refreshUrl) ?>, function (data) {
				$('#techs-ports-<?= $tech->id ?>').html(data);
			});
		}, 15000);
	</script>
<?php } ?>
