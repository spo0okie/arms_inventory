<?php
/**
 * Панель Zabbix: активные проблемы узла + ссылка на него в веб-интерфейсе.
 * Данные нормализованы в ZabbixProvider::fetchProblems().
 */

use yii\helpers\Html;

/* @var $notFound bool узел не найден в Zabbix */
/* @var $problems array[] см. ZabbixProvider::fetchProblems() */
/* @var $hostid string|null */
/* @var $hostUrl string|null ссылка на узел в Zabbix (L0) */
/* @var $model \app\models\base\ArmsModel */
/* @var $provider \app\components\integrations\providers\ZabbixProvider */

if ($notFound) {
	echo '<span class="text-secondary opacity-75">узел не найден в Zabbix</span>';
	return;
}

//классы бейджа важности Zabbix: 0-1 серый, 2 инфо, 3 предупр., 4-5 опасность
$severityClass = static function (int $severity): string {
	if ($severity >= 4) return 'bg-danger';
	if ($severity === 3) return 'bg-warning text-dark';
	if ($severity === 2) return 'bg-info text-dark';
	return 'bg-secondary';
};

$formatter = Yii::$app->formatter;

?>
<div class="d-flex justify-content-between align-items-center mb-1">
	<span>
		<?php if (count($problems)) { ?>
			<span class="badge bg-danger"><?= count($problems) ?></span> активных проблем
		<?php } else { ?>
			<span class="badge bg-success">OK</span> проблем нет
		<?php } ?>
	</span>
	<?php if ($hostUrl) { ?>
		<small><?= Html::a('в Zabbix <i class="fas fa-external-link-alt"></i>', $hostUrl,
			['target' => '_blank', 'rel' => 'noopener']) ?></small>
	<?php } ?>
</div>
<?php foreach ($problems as $problem) { ?>
	<div class="mt-1">
		<span class="badge <?= $severityClass($problem['severity']) ?>"><?= Html::encode($problem['severity_name']) ?></span>
		<?= Html::encode($problem['name']) ?>
		<?php if (!empty($problem['since'])) { ?>
			<small class="text-secondary">с <?= $formatter->asDatetime($problem['since'], 'php:d.m.Y H:i') ?></small>
		<?php } ?>
	</div>
<?php } ?>
