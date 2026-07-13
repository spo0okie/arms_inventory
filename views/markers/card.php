<?php

use app\components\ItemObjectWidget;
use app\components\ModelFieldWidget;
use yii\helpers\Html;

/**
 * Карточка маркера для view и tooltip
 *
 * @var yii\web\View $this
 * @var app\models\Markers $model
 * @var $static_view
 */

?>

<div class="markers-card">

	<h1>
		<?= $model->renderItem($this, [
			'static_view' => $static_view,
			'noDelete' => $static_view,
			'hideUndeletable' => false,
		]) ?>
	</h1>

	<?= ModelFieldWidget::widget([
		'model' => $model,
		'field' => 'comment',
	]) ?>

	<?php if (!$static_view) {
		//примеры отображения на типовых фонах приложения — только на странице
		//просмотра (в тултипе не нужны). Фоны НЕ хардкодим: подложки берут
		//классы самих контекстов (nav-header, тема tooltipster, arms-card),
		//так что будущие темы оформления перекрасят и превью.
		$specimen = ItemObjectWidget::widget([
			'model' => $model,
			'marker' => $model,
			'show_archived' => true,
			'link' => Html::a('Пример текста (буквы с выносами: руду)', '#', ['onclick' => 'return false']),
		]);
		$scenarios = [
			//[подпись, класс внешней обертки, класс подложки]
			['Общий фон', '', ''],
			['Тёмный фон шапки', '', 'nav-header'],
			['Тултип', 'tooltipster-sidetip tooltipster-shadow tooltipster-shadow-yellow', 'tooltipster-box'],
			['Тёмная карточка', 'users-view', 'arms-card'],
		];
	?>
		<h4 class="mt-3">Примеры отображения</h4>
		<div class="marker-preview">
			<?php //подложки и обертки — div: контексты в CSS объявлены элементно (div.nav-header, div.users-view div.arms-card) ?>
			<?php foreach ($scenarios as [$label, $outerClass, $bgClass]) { ?>
				<div class="marker-preview-row">
					<span class="marker-preview-label text-muted"><?= $label ?></span>
					<div class="<?= $outerClass ?>">
						<div class="marker-preview-bg <?= $bgClass ?>"><?= $specimen ?></div>
					</div>
				</div>
			<?php } ?>
			<div class="marker-preview-row">
				<span class="marker-preview-label text-muted">Компактный уголок</span>
				<div class="marker-preview-bg text-nowrap">
					<span class="unit-status marked-item" style="<?= $model->styleVars ?>"><?= Html::encode($model->name) ?></span>
				</div>
			</div>
		</div>
	<?php } ?>

</div>
