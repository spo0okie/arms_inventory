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

$status = $data['status'] ?? null;

//неопрошенная коммутатор тут ровно одна - это он сам
$failure = ($data['errors'] ?? [])[0] ?? null;

//что помешало опросу (в порядке убывания «это вообще не наши данные»)
$trouble = null;
if ($error) $trouble = 'опрос не выполнен: '.$error;
elseif ($status === 'error') $trouble = 'опрос не выполнен: '.($data['error'] ?? 'ошибка сервиса');
elseif ($failure) $trouble = 'коммутатор не опрошен: '.($failure['error'] ?? 'причина не указана');

?>
<?php if ($status === 'pending') { ?>
	<div class="text-secondary mb-1">
		<span class="spinner-border spinner-border-sm" role="status"></span>
		&mdash; идёт опрос коммутатора<?= $refreshUrl ? '' : ', откройте карточку позже' ?>
	</div>
<?php } elseif ($trouble) { ?>
	<div class="text-secondary opacity-75 mb-1"<?= empty($failure['detail']) ? ''
		: ' qtip_ttip="'.Html::encode($failure['detail']).'"' ?>><?= Html::encode($trouble) ?></div>
<?php } ?>

<?php /* путь алиасом, а не '/techs/...': вид рендерится не из контроллера
       techs, а из proxy интеграций - относительный путь там не резолвится */ ?>
<?= $this->render('@app/views/techs/_ports-table', ['model' => $tech, 'ports' => $ports,
	//паспорт передаём отдельно: в таблице порты идут в объявленном порядке, а
	//«назвать порты как на коммутаторе» должно взять и порядок коммутатора тоже
	'passport' => $data['ports'] ?? [],
	'transitFrom' => $provider->transitFrom(),
	'scanStamp' => $provider->scanStamp($data)]) ?>

<?php if (!$trouble && $status !== 'pending') { ?>
	<?php /* сырые данные - свёрнутыми: на основной таблице они мозолят глаза,
	       но если человек полез за подробностями, прятать их незачем. Тут же
	       и адреса транзитных портов, которых в основной таблице нет */ ?>
	<details class="text-secondary small mb-2">
		<summary>показать данные с коммутатора</summary>
		<?= $this->render('_raw', ['data' => $data]) ?>
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
