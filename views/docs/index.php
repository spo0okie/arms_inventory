<?php

/* @var $this yii\web\View */
/* @var $pages array список страниц ['path'=>..., 'title'=>...] из DocsHelper::pagesList() */
/* @var $models array справочник сущностей ['classId','titles','description','hasPage'] */

use yii\helpers\Html;

$this->title = 'Документация';
$this->params['breadcrumbs'][] = $this->title;

//известные разделы конвенции (docs/help/README.md); прочие каталоги показываем как есть.
//раздел models в списке файлов не выводится: сущности перечислены в справочнике ниже
$sectionTitles = [
	'' => 'Общее',
	'guides' => 'Сценарии работы',
	'admin' => 'Администрирование',
	'types' => 'Типы данных',
];

$sections = [];
foreach ($pages as $page) {
	$tokens = explode('/', $page['path']);
	$section = count($tokens) > 1 ? $tokens[0] : '';
	if ($section === 'models') continue;
	$sections[$section][] = $page;
}

//сортируем разделы в порядке конвенции, неизвестные - в конец по алфавиту
uksort($sections, function ($a, $b) use ($sectionTitles) {
	$order = array_flip(array_keys($sectionTitles));
	$aOrder = $order[$a] ?? count($order);
	$bOrder = $order[$b] ?? count($order);
	return $aOrder <=> $bOrder ?: strcmp($a, $b);
});

?>
<h1><?= Html::encode($this->title) ?></h1>

<?php if (\app\helpers\DocsHelper::pageExists('guides/getting-started.md')) { ?>
	<p class="lead">
		<?= Html::a('▶ С чего начать', ['page', 'path' => 'guides/getting-started.md'], ['class' => 'btn btn-primary']) ?>
		<span class="text-muted">— если вы здесь впервые.</span>
	</p>
<?php } ?>

<?php foreach ($sections as $section => $sectionPages) { ?>
	<h4 class="mt-4"><?= Html::encode($sectionTitles[$section] ?? $section) ?></h4>
	<ul>
		<?php foreach ($sectionPages as $page) { ?>
			<li>
				<?= Html::a(Html::encode($page['title']), ['page', 'path' => $page['path']]) ?>
				<span class="text-muted small"><?= Html::encode($page['path']) ?></span>
			</li>
		<?php } ?>
	</ul>
<?php } ?>

<h4 class="mt-4">Справочник сущностей</h4>
<p class="text-muted small">
	Все сущности системы. Страница сущности содержит описание и полный
	перечень атрибутов с подсказками; у отмеченных <b>&#9998;</b> есть
	подробное описание.
</p>
<table class="table table-sm table-striped">
	<tbody>
	<?php foreach ($models as $entry) { ?>
		<tr>
			<td>
				<?= Html::a(Html::encode($entry['titles']), ['model', 'class' => $entry['classId']]) ?>
				<?= $entry['hasPage'] ? '&#9998;' : '' ?>
				<div class="text-muted small"><?= Html::encode($entry['classId']) ?></div>
			</td>
			<td><?= $entry['description'] ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
