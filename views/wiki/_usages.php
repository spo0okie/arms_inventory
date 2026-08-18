<?php

use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var array $usages места использования одной страницы (см. WikiLinksScanner) */
/** @var array $kindLabels подписи видов ссылок (WikiLinksScanner::KIND_*) */
/** @var int $limit сколько мест показывать сразу (остальные - под "ещё N") */

if (!isset($kindLabels)) $kindLabels=[];
if (!isset($limit)) $limit=10;

/**
 * Список мест использования: первые $limit сразу, остальные - в раскрывашке
 * (на страницу-шаблон может ссылаться несколько сотен объектов)
 */
$renderList=function(array $list) use ($kindLabels,$limit) {
	$out='';
	foreach (array_slice($list,0,$limit) as $usage)
		$out.=$this->render('_usage',['usage'=>$usage,'kindLabels'=>$kindLabels]);

	if (count($list)>$limit) {
		$rest='';
		foreach (array_slice($list,$limit) as $usage)
			$rest.=$this->render('_usage',['usage'=>$usage,'kindLabels'=>$kindLabels]);
		$out.=Html::tag('details',
			Html::tag('summary','ещё '.(count($list)-$limit),['class'=>'small text-muted'])
			.$rest
		);
	}

	return $out;
};

//находки внутри включенных страниц - это ОДНА ссылка самой wiki, попавшая
//в объекты через включение. Поэтому они не перечисляются по объектам подряд,
//а сворачиваются в строку "найдено во включенной странице X - объектов: N"
$direct=[];
$nested=[];
foreach ($usages as $usage) {
	$via=$usage['via']??[];
	if (count($via)) $nested[implode(' → ',$via)][]=$usage;
	else $direct[]=$usage;
}

echo $renderList($direct);

foreach ($nested as $chain=>$list) {
	echo Html::tag('div',
		'найдено во включённой странице <code>'.Html::encode($chain).'</code>'
		.' — объектов: '.count($list),
		['class'=>'small text-muted mt-1']
	);
	echo Html::tag('details',
		Html::tag('summary','показать объекты',['class'=>'small text-muted'])
		.$renderList($list)
	);
}
