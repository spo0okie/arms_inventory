<?php
/**
 * Матрица межсегментного доступа (issue #220, plans/access-chains.md итерация 4).
 *
 * Строки — откуда (сегмент-субъект записи доступа), колонки — куда (сегмент-ресурс
 * списка доступа). В ячейке — типы доступа с сетевыми параметрами; каждая запись —
 * ссылка на свою запись доступа. Пустая ячейка — доступа «сегмент → сегмент» нет.
 *
 * В матрицу входят ТОЛЬКО доступы, у которых ресурсом обозначен именно сегмент, а
 * субъектом — тоже сегмент. Точечные доступы к узлам/сервисам/сетям сегмента видны
 * на странице сети («Вх. соединения»), доступы к сегменту от несегментных
 * субъектов — на странице сегмента.
 *
 * @var yii\web\View $this
 * @var app\models\Segments[] $segments
 * @var array $matrix [id субъекта][id ресурса] => Aces[]
 */

use app\components\widgets\page\ModelWidget;
use app\models\Segments;
use yii\helpers\Html;

$this->title = 'Матрица межсегментного доступа';
$this->params['breadcrumbs'][] = ['label' => Segments::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$renderSegment = static function (Segments $segment) {
	return ModelWidget::widget(['model' => $segment, 'options' => ['static_view' => true]]);
};
?>
<div class="segments-matrix">
	<h1><?= Html::encode($this->title) ?></h1>
	<p class="text-muted">
		Строки — <b>откуда</b>, колонки — <b>куда</b>. В ячейке — какой доступ разрешён из сегмента в сегмент.
		<span class="fas fa-question-circle" qtip_ttip="В матрицу попадают только списки доступа, у которых ресурсом обозначен
			именно сегмент, с записями, где субъект — тоже сегмент.<br>Точечные доступы к конкретным узлам, сервисам и сетям
			сегмента смотрите на странице сети (вкладка «Вх. соединения»), доступы к сегменту от сервисов, сетей
			и адресов — на странице сегмента." qtip_side="bottom"></span>
	</p>

	<?php if (!count($segments)) { ?>
		<p><i>Сегменты не заведены</i></p>
	<?php } else { ?>
	<div class="table-responsive">
		<table class="table table-bordered table-sm align-middle segments-matrix-table">
			<thead>
			<tr>
				<th class="text-end small text-muted">откуда ↓ &nbsp; куда →</th>
				<?php foreach ($segments as $resource) { ?>
					<th class="text-center"><?= $renderSegment($resource) ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($segments as $subject) { ?>
				<tr>
					<th class="text-nowrap"><?= $renderSegment($subject) ?></th>
					<?php foreach ($segments as $resource) {
						$aces = $matrix[$subject->id][$resource->id] ?? [];
						$lines = [];
						foreach ($aces as $ace) {
							$types = [];
							$ipParams = $ace->getIpParams();
							foreach ($ace->accessTypes as $type) {
								$params = trim((string)($ipParams[$type->id] ?? ''));
								$types[] = Html::encode($type->name) . ($params !== '' ? ': ' . Html::encode($params) : '');
							}
							if (!count($types)) $types[] = Html::encode(\app\models\Aces::$noAccessName);
							//запись целиком — ссылка на ACE (подробности и правка там)
							$lines[] = $this->render('/aces/item', [
								'model' => $ace, 'static_view' => true,
								'name' => implode(', ', $types),
							]);
						}
						$class = count($lines) ? 'table-success' : ($subject->id == $resource->id ? 'table-light' : '');
						echo Html::tag('td', implode('<br />', $lines), ['class' => 'small text-center ' . $class]);
					} ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
	<?php } ?>
</div>
