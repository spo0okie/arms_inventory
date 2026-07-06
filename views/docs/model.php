<?php

/* @var $this yii\web\View */
/* @var $classId string kebab-case id модели */
/* @var $model app\models\base\ArmsModel пустой экземпляр модели */
/* @var $html string отрендеренный MD models/<class-id>.md (или '') */

use app\components\AttributeTooltip;
use app\helpers\DocsHelper;
use app\helpers\StringHelper;
use yii\helpers\Html;

$titles = $model::$titles;
$this->title = 'Документация: ' . $titles;
$this->params['breadcrumbs'][] = ['label' => 'Документация', 'url' => ['index']];
$this->params['breadcrumbs'][] = $titles;

$indexController = StringHelper::class2Controller(get_class($model));
$indexUrl = class_exists($indexController) ? ['/' . $classId . '/index'] : null;

?>
<h1>
	<?= Html::encode($titles) ?>
	<?php if ($indexUrl) { ?>
		<small><?= Html::a('перейти к списку', $indexUrl, ['class' => 'btn btn-outline-secondary btn-sm']) ?></small>
	<?php } ?>
</h1>

<?php if ($description = $model::modelDescription()) { ?>
	<p class="lead"><?= $description ?></p>
<?php } ?>

<?php if ($html) { ?>
	<div class="docs-page">
		<?= $html ?>
	</div>
<?php } ?>

<h4 class="mt-4">Атрибуты</h4>
<table class="table table-sm table-striped">
	<thead>
	<tr>
		<th>Атрибут</th>
		<th>Описание</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach (array_keys($model->attributeData()) as $attr) {
		try {
			$label = $model->getAttributeLabel($attr);
			//смысл + формат типа + переходы на подробные страницы - собирает сборщик
			$tooltip = AttributeTooltip::build($model, $attr);
		} catch (Throwable $e) {
			//атрибут с невыводимыми метаданными не должен ронять страницу документации
			$label = $attr;
			$tooltip = null;
		}
		?>
		<tr>
			<td>
				<?= Html::encode($label) ?>
				<div class="text-muted small"><?= Html::encode($attr) ?></div>
			</td>
			<td>
				<?= $tooltip['body'] ?? '' ?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>

<?php
//футер: подробные страницы атрибутов (models/<class-id>/<attr>.md) рендерятся
//абзацами - страница сущности читается как единый документ, собранный из слоёв
$attrDetails = [];
foreach (array_keys($model->attributeData()) as $attr) {
	$attrPath = DocsHelper::attributePagePath($classId, $attr);
	if ($file = DocsHelper::findPage($attrPath)) {
		try {
			$label = $model->getAttributeLabel($attr);
		} catch (Throwable $e) {
			$label = $attr;
		}
		$attrDetails[] = [
			'label' => $label,
			'html' => DocsHelper::renderPage($file, $attrPath, true),
		];
	}
}
?>
<?php if (count($attrDetails)) { ?>
	<h2 class="mt-4">Подробно об атрибутах</h2>
	<?php foreach ($attrDetails as $detail) { ?>
		<h3 class="mt-3"><?= Html::encode($detail['label']) ?></h3>
		<div class="docs-page">
			<?= $detail['html'] ?>
		</div>
	<?php } ?>
<?php } ?>
