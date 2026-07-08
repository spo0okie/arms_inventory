<?php

/* @var $this yii\web\View */
/* @var $classId string kebab-case id модели */
/* @var $model app\models\base\ArmsModel пустой экземпляр модели */
/* @var $html string отрендеренный MD models/<class-id>.md (или '') */

use app\components\AttributeTooltip;
use app\components\assets\MermaidAsset;
use app\helpers\StringHelper;
use yii\helpers\Html;

MermaidAsset::register($this);

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

<?php
//короткое описание (modelDescription) - слой тултипов; на полной странице
//документации его роль играет преамбула MD-страницы, показываем только как
//фолбэк, когда MD-страницы нет (иначе одно и то же дважды)
if (!$html && ($description = $model::modelDescription())) { ?>
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
//подробные страницы атрибутов НЕ разворачиваются здесь вторым сборником:
//в таблице выше на них уже есть ссылки «подробнее: ...» (блок 3 сборщика),
//открываются модалками - «ссылка вместо пересказа»
?>
