<?php

use app\components\CollapsableCardWidget;
use app\helpers\StringHelper;
use app\helpers\WikiLinksScanner;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var array $pages страницы этой wiki (WikiLinksScanner::getWikiPages()) */
/** @var array $interwiki интервики-ссылки по shortcut (WikiLinksScanner::getInterwiki()) */
/** @var array $attributes просканированные поля с разметкой: FQCN => атрибуты */
/** @var array $urlAttributes просканированные поля-ссылки: FQCN => атрибуты */
/** @var array $totals счетчики прогона */
/** @var array $failures страницы wiki, которые не удалось получить */
/** @var string $wikiUrl база URL подключенной wiki */
/** @var bool $followIncludes шли ли за включениями {{page>...}} */
/** @var bool $includeNested учитывались ли находки внутри включенных страниц */
/** @var int $depth предельная глубина обхода включений */

$this->title='Ссылки в wiki';
$this->params['breadcrumbs'][]=$this->title;

//подписи видов ссылок (WikiLinksScanner::KIND_*)
$kindLabels=[
	WikiLinksScanner::KIND_LINK=>'вики-ссылка',
	WikiLinksScanner::KIND_INCLUDE=>'включение',
	WikiLinksScanner::KIND_URL=>'URL',
];

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

/**
 * Адрес страницы в самой wiki
 * @param string $page
 * @return string
 */
$pageUrl=function($page) use ($wikiUrl) {
	return rtrim($wikiUrl,'/').'/doku.php?id='.rawurlencode($page);
};

?>
<h1><?= Html::encode($this->title) ?></h1>

<p class="text-muted">
	На какие страницы wiki ссылается инвентаризация. Встроенный в DokuWiki
	список «Ссылки сюда» знает только про ссылки внутри самой wiki - этот отчет
	показывает ссылки извне: вики-ссылки <code>[[namespace:страница]]</code> и
	включения <code>{{page&gt;...}}</code> в полях с разметкой, а также адреса
	страниц wiki в полях-списках ссылок.
	<?= Html::a('Подробнее об интеграции',['/docs/page','path'=>'admin/integrations/dokuwiki.md']) ?>.
</p>

<p>
	Просканировано полей с разметкой: <b><?= (int)$totals['attributes'] ?></b>,
	полей-ссылок: <b><?= (int)$totals['urlAttributes'] ?></b>,
	сущностей: <b><?= (int)$totals['classes'] ?></b>.
	Найдено ссылок в wiki: <b><?= (int)$totals['refs'] ?></b>,
	страниц: <b><?= (int)$totals['pages'] ?></b>,
	объектов-источников: <b><?= (int)$totals['objects'] ?></b><?php
	if ($totals['nested']) { ?> (из них через включения: <b><?= (int)$totals['nested'] ?></b>)<?php } ?>.
	Интервики-ссылок (в другие wiki): <b><?= (int)$totals['interwiki'] ?></b>.
</p>

<p>
	<?php if ($followIncludes) { ?>
		<?= Html::a('Не ходить за включениями',
			['links','includes'=>0,'nested'=>(int)$includeNested,'depth'=>$depth],
			['class'=>'btn btn-sm btn-outline-secondary']) ?>
	<?php } else { ?>
		<?= Html::a('Идти за включениями {{page&gt;...}}',
			['links','includes'=>1,'nested'=>(int)$includeNested,'depth'=>$depth],
			['class'=>'btn btn-sm btn-outline-secondary']) ?>
	<?php } ?>
	<?php if ($includeNested) { ?>
		<?= Html::a('Только ссылки самой инвентаризации',
			['links','includes'=>(int)$followIncludes,'nested'=>0,'depth'=>$depth],
			['class'=>'btn btn-sm btn-outline-secondary']) ?>
		<span class="text-muted small">
			сейчас учтены и ссылки, написанные внутри включённых страниц: это ссылки
			самой wiki, и одна такая попадает в отчёт по разу на каждый объект,
			который втягивает страницу (глубина обхода: <?= (int)$depth ?>)
		</span>
	<?php } else { ?>
		<?= Html::a('Учитывать и ссылки внутри включённых страниц',
			['links','includes'=>(int)$followIncludes,'nested'=>1,'depth'=>$depth],
			['class'=>'btn btn-sm btn-outline-secondary']) ?>
		<span class="text-muted small">
			сейчас учтено только то, что написано в самой инвентаризации;
			из включённых страниц учитывается сам факт включения
		</span>
	<?php } ?>
</p>

<?php

//что именно сканировалось
$scanned='';
$scannedGroups=[
	'Поля с разметкой DokuWiki'=>$attributes,
	'Поля-списки ссылок'=>$urlAttributes,
];
foreach ($scannedGroups as $groupTitle=>$groupAttributes) {
	$scanned.='<div class="fw-bold mt-1">'.Html::encode($groupTitle).'</div>';
	if (!count($groupAttributes)) {
		$scanned.='<div class="text-muted">нет таких полей</div>';
		continue;
	}
	$scanned.='<ul class="mb-0">';
	foreach ($groupAttributes as $class=>$classAttributes) {
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
}

echo CollapsableCardWidget::widget([
	'title'=>'Просканированные поля ('
		.((int)$totals['attributes']+(int)$totals['urlAttributes']).')',
	'buttonClass'=>'btn btn-sm btn-link px-0',
	'contentClass'=>'small',
	'content'=>$scanned,
	'initialCollapse'=>true,
]);

if ($wikiUrl==='') { ?>
	<div class="alert alert-warning mt-3">
		Wiki не подключена (не задан <code>wikiUrl</code> в config/params-local.php) -
		ссылки на ее страницы распознать невозможно.
	</div>
<?php }

if (count($failures)) { ?>
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
<?php }

if (!count($pages)) {
	echo '<div class="alert alert-secondary mt-3">Ссылок на страницы wiki не найдено</div>';
} else { ?>
	<h4 class="mt-4">
		Страницы wiki
		<span class="text-muted small">
			страниц: <?= count($pages) ?>, ссылок: <?= (int)$totals['refs'] ?>
		</span>
	</h4>
	<table class="table table-sm table-striped">
		<thead>
		<tr>
			<th style="width:35%">Страница</th>
			<th style="width:5%" class="text-end">Ссылок</th>
			<th>Откуда ссылаются</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($pages as $page) { ?>
			<tr>
				<td>
					<?= $wikiUrl?
						Html::a('<code>'.Html::encode($page['page']).'</code>',$pageUrl($page['page']),
							['target'=>'_blank','title'=>'Открыть страницу в wiki']):
						'<code>'.Html::encode($page['page']).'</code>' ?>
					<div class="text-muted small">
						<?php foreach ($page['kinds'] as $kind=>$count) { ?>
							<?= Html::encode($kindLabels[$kind]??$kind) ?>: <?= (int)$count ?>&nbsp;
						<?php } ?>
					</div>
					<?php
					//сколько ссылок пришло не из инвентаризации, а из текста включенных страниц
					$nestedCount=count(array_filter($page['usages'],function($usage) {
						return count($usage['via']??[]);
					}));
					if ($nestedCount) { ?>
						<div class="text-muted small">из них через включения: <?= $nestedCount ?></div>
					<?php } ?>
				</td>
				<td class="text-end"><?= (int)$page['count'] ?></td>
				<td>
					<?= $this->render('_usages',[
						'usages'=>$page['usages'],
						'kindLabels'=>$kindLabels,
					]) ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php }

if (!count($interwiki)) return;
?>
	<h4 class="mt-4">Интервики-ссылки <span class="text-muted small">(в другие wiki)</span></h4>
<?php foreach ($interwiki as $group) { ?>
	<h5 class="mt-3">
		<code><?= Html::encode($group['shortcut']) ?>&gt;</code>
		<span class="text-muted small">
			ссылок: <?= (int)$group['count'] ?>, страниц: <?= count($group['targets']) ?>
		</span>
	</h5>
	<table class="table table-sm table-striped">
		<thead>
		<tr>
			<th style="width:35%">Страница</th>
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
					<?= $this->render('_usages',[
						'usages'=>$target['usages'],
						'kindLabels'=>$kindLabels,
					]) ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
