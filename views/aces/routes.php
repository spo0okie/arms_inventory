<?php
/**
 * Маршруты транзита (plans/access-chains.md, итерация 3): цепочки записей доступа,
 * связанных указателями «следующий хоп».
 *
 * Строка на маршрут: «субъекты первого хопа →[тип: параметры] ресурс →[…] ресурс».
 * Хоп, с которого смотрим ($current), выделен: по нему видно, какое место запись
 * занимает в маршруте — начало, транзит через посредника или конечный ресурс.
 * Стык, где субъекты следующего хопа не содержат ресурс предыдущего, помечается
 * предупреждением (мягкая проверка, не запрет).
 *
 * Модели хопов должны быть загружены с Aces::ROUTES_WITH (см. Aces::routesOf()).
 *
 * @var yii\web\View $this
 * @var app\models\Aces[][] $routes  маршруты: списки хопов по порядку
 * @var int|int[]|null $current      ID записи (записей), с которой смотрим
 * @var string $glue
 */

use app\components\widgets\page\ModelWidget;
use app\models\Aces;
use yii\helpers\Html;

if (empty($routes)) return;
if (!isset($glue)) $glue = '<br />';
$current = array_map('intval', (array)($current ?? []));

$renderObject = static function ($object) {
	return is_object($object)
		? ModelWidget::widget(['model' => $object, 'options' => ['static_view' => true, 'short' => true]])
		: Html::encode((string)$object);
};

$lines = [];
foreach ($routes as $hops) {
	/** @var Aces $first */
	$first = reset($hops);
	$subjects = [];
	foreach ($first->subjects as $subject) $subjects[] = $renderObject($subject);
	$line = Html::tag('span', implode(', ', $subjects), ['class' => 'route-subjects']);

	$prev = null;
	foreach ($hops as $hop) {
		//подпись стрелки: типы доступа с сетевыми параметрами этого хопа
		$labels = [];
		$ipParams = $hop->getIpParams();
		foreach ($hop->accessTypes as $type) {
			$params = trim((string)($ipParams[$type->id] ?? ''));
			$labels[] = Html::encode($type->name) . ($params !== '' ? ' ' . Html::encode($params) : '');
		}

		//стрелка — ссылка на запись доступа этого хопа
		$arrow = $this->render('/aces/item', [
			'model' => $hop, 'static_view' => true,
			'name' => '<span class="fas fa-long-arrow-alt-right"></span>',
		]);

		$warning = '';
		if ($prev && !Aces::transitJoint($prev, $hop)) {
			$warning = Html::tag('span', '', [
				'class' => 'fas fa-exclamation-triangle text-warning mx-1',
				'qtip_ttip' => 'Стык не сходится: среди субъектов этого хопа нет ресурса предыдущего.<br>'
					. 'Возможно, субъект задан шире (сеть) — либо маршрут собран неверно',
			]);
		}

		$resource = is_object($hop->acl) ? $hop->acl->resource : null;
		//проброшенный хоп (NAT, реверс-прокси) помечаем: на нём субъект - адрес входа
		$forward = $hop->is_forward ? Html::tag('span', '', [
			'class' => 'fas fa-random small opacity-75 me-1',
			'qtip_ttip' => 'Проброс соединения (NAT, реверс-прокси)',
		]) : '';

		$segment = ' ' . $warning . $arrow . $forward
			. (count($labels) ? Html::tag('span', implode(', ', $labels), ['class' => 'small text-monospace opacity-75 me-1']) : '')
			. $renderObject($resource);

		$line .= Html::tag('span', $segment, [
			'class' => 'route-hop' . (in_array((int)$hop->id, $current) ? ' fw-bold' : ''),
		]);
		$prev = $hop;
	}
	$lines[] = Html::tag('span', $line, ['class' => 'route-line text-nowrap']);
}

echo implode($glue, $lines);
