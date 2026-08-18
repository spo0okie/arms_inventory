<?php

use app\components\CollapsableCardWidget;
use app\components\ItemObjectWidget;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var array $groups сгруппированные ссылки (WikiLinksScanner::group()) */
/** @var array $attributes просканированные атрибуты: FQCN => список атрибутов */
/** @var array $totals счетчики прогона */
/** @var array $failures страницы wiki, которые не удалось получить */
/** @var bool $followIncludes шли ли за включениями {{page>...}} */
/** @var int $depth предельная глубина обхода включений */

$this->title='Интервики-ссылки';
$this->params['breadcrumbs'][]=$this->title;

/**
 * Имя сущности для заголовков (у моделей ARMS - статический $titles)
 * @param string $class
 * @return string
 */
$classTitle=function($class) {
	/** @var ArmsModel $class */
	if (property_exists($class,'titles') && $class::$titles) return $class::$titles;
	return StringHelper::className($class);
};

?>
<h1><?= Html::encode($this->title) ?></h1>

<p class="text-muted">
	Ссылки вида <code>[[shortcut&gt;страница]]</code>, найденные в полях
	инвентаризации с разметкой DokuWiki. Учитывается и содержимое страниц wiki,
	включенных в поле через <code>{{page&gt;...}}</code> / <code>{{section&gt;...}}</code>
	(плагин include) - такие ссылки помечены цепочкой включений.
	<?= Html::a('Подробнее об интеграции',['/docs/page','path'=>'admin/integrations/dokuwiki.md']) ?>.
</p>

<p>
	Просканировано атрибутов: <b><?= (int)$totals['attributes'] ?></b>,
	сущностей: <b><?= (int)$totals['classes'] ?></b>,
	полей с разметкой: <b><?= (int)$totals['texts'] ?></b>.
	Найдено ссылок: <b><?= (int)$totals['links'] ?></b>,
	shortcut'ов: <b><?= count($groups) ?></b>,
	объектов: <b><?= (int)$totals['objects'] ?></b>.
	Загружено включенных страниц wiki: <b><?= (int)$totals['includes'] ?></b>.
</p>

<p>
	<?php if ($followIncludes) { ?>
		<?= Html::a('Не ходить за включениями',['interwiki','includes'=>0],['class'=>'btn btn-sm btn-outline-secondary']) ?>
		<span class="text-muted small">включения обходятся на <?= (int)$depth ?> уровня</span>
	<?php } else { ?>
		<?= Html::a('Идти за включениями {{page&gt;...}}',['interwiki','includes'=>1],[
			'class'=>'btn btn-sm btn-outline-secondary',
		]) ?>
		<span class="text-muted small">сейчас сканируются только сами поля, без запросов к wiki</span>
	<?php } ?>
</p>

<?php if (count($failures)) { ?>
	<div class="alert alert-warning">
		Не удалось получить из wiki (страница отсутствует, нет прав или wiki не настроена)
		- ссылки из этих страниц в отчет не попали:
		<ul class="mb-0">
			<?php foreach ($failures as $page=>$source) { ?>
				<li><code><?= Html::encode($page) ?></code>
					<span class="text-muted small">включена в <?= Html::encode($source) ?></span></li>
			<?php } ?>
		</ul>
	</div>
<?php } ?>

<?php

//что именно сканировалось: список полей с dokuwiki-разметкой (params['textFields'])
$scanned='';
if (count($attributes)) {
	$scanned.='<ul class="mb-0">';
	foreach ($attributes as $class=>$classAttributes) {
		$labels=[];
		try {
			/** @var ArmsModel $sample */
			$sample=new $class();
			foreach ($classAttributes as $attribute)
				$labels[]=$sample->getAttributeViewLabel($attribute).' ('.$attribute.')';
		} catch (\Throwable $e) {
			$labels=$classAttributes;
		}
		$scanned.='<li>'.Html::encode($classTitle($class)).': '
			.Html::encode(implode(', ',$labels)).'</li>';
	}
	$scanned.='</ul>';
} else {
	$scanned='<div class="alert alert-secondary mb-0">Ни один атрибут не рендерится через DokuWiki: '
		.'проверьте <code>textFields</code> в config/params-local.php</div>';
}

echo CollapsableCardWidget::widget([
	'title'=>'Просканированные поля ('.(int)$totals['attributes'].')',
	'buttonClass'=>'btn btn-sm btn-link px-0',
	'contentClass'=>'small',
	'content'=>$scanned,
	'initialCollapse'=>true,
]);

if (!count($groups)) {
	echo '<div class="alert alert-secondary mt-3">Интервики-ссылок не найдено</div>';
	return;
}

foreach ($groups as $group) { ?>
	<h4 class="mt-4">
		<code><?= Html::encode($group['shortcut']) ?>&gt;</code>
		<span class="text-muted small">
			ссылок: <?= (int)$group['count'] ?>, страниц: <?= count($group['targets']) ?>
		</span>
	</h4>
	<table class="table table-sm table-striped">
		<thead>
		<tr>
			<th style="width:40%">Страница</th>
			<th style="width:5%" class="text-end">Ссылок</th>
			<th>Где встречается</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($group['targets'] as $target) { ?>
			<tr>
				<td><code><?= Html::encode($target['target']) ?></code></td>
				<td class="text-end"><?= (int)$target['count'] ?></td>
				<td>
					<?php foreach ($target['usages'] as $usage) {
						/** @var ArmsModel $model */
						$model=$usage['model'];
						$attribute=$usage['attribute'];
						//имя объекта может быть и колонкой, и вычисляемым (или отсутствовать вовсе)
						try { $name=$model->name; } catch (\Throwable $e) { $name=null; }
						if (!strlen((string)$name)) $name='#'.$usage['id'];
						?>
						<div>
							<?= ItemObjectWidget::widget([
								'model'=>$model,
								'name'=>$name,
								'noUpdate'=>true,
								'noDelete'=>true,
								'show_archived'=>true,
							]) ?>
							<span class="text-muted small">
								<?= Html::encode($classTitle($usage['class'])) ?>
								&rarr; <?= Html::encode($model->getAttributeViewLabel($attribute)) ?>
							</span>
							<?php if (count($usage['via'])) { ?>
								<span class="text-muted small">
									через <?php foreach ($usage['via'] as $via) { ?>
										<code>{{page&gt;<?= Html::encode($via) ?>}}</code>
									<?php } ?>
								</span>
							<?php } ?>
							<?php if ($usage['title']!=='') { ?>
								<span class="text-muted small">подпись: <?= Html::encode($usage['title']) ?></span>
							<?php } ?>
						</div>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
