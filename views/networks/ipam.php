<?php

use yii\helpers\Url;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $models app\models\Networks */
/* @var $baseIp string */
/* @var $maxPrefix integer */
/* @var $minPrefix integer */

Url::remember();

$this->title = 'IPAM';
$this->params['breadcrumbs'][] = app\models\Networks::$titles;
$this->params['breadcrumbs'][] = $this->title;


/*$baseIp = '192.168.0.0'; // или получай из запроса
$maxPrefix = 16;
$minPrefix = 24;*/
$baseAddr = ip2long($baseIp);
$baseSize = 1 << (32 - $maxPrefix);
$baseEnd = $baseAddr + $baseSize - 1;
$rows=1 << ($minPrefix - $maxPrefix);
$height=20*$rows;

// Индекс: [$addr][$prefix] => модель
$map = [];
foreach ($models as $m) {
	$prefix = $m->IPv4Block()->getPrefixLength();
	$map[$m->addr][$prefix] = $m;
}

function generateBlocks(int $start, int $end, int $prefix): array {
	$step = 1 << (32 - $prefix);
	$blocks = [];
	for ($addr = $start; $addr <= $end; $addr += $step) {
		$blocks[] = [$addr, $prefix];
	}
	return $blocks;
}


echo $this->render('ipam-form',compact('baseIp','maxPrefix','minPrefix'));

// Контейнер всей сетки
echo Html::beginTag('div', [
	'class' => 'd-flex flex-row justify-content-center',
	//'style' => 'height:'.($rows*20).'px'
]);

// Колонки слева направо: от minPrefix до maxPrefix
for ($prefix = $maxPrefix; $prefix <= $minPrefix; $prefix++) {
	$blocks = generateBlocks($baseAddr, $baseEnd, $prefix);
	$blockCount = count($blocks);
	
	echo Html::beginTag('div', [
		'class' => 'ipam-column',
		'data-column' => $prefix, // уникальный ID колонки
	]);
	
	foreach ($blocks as [$addr, $p]) {
		$model = $map[$addr][$p] ?? null;
		$heightPercent = 100 / $blockCount;
		$heightPx = ($height / $blockCount) -2; //убираем отступ из высоты
		$cidr = long2ip($addr) . '/' . $p;
		
		echo Html::beginTag('span', [
			'class' => 'ipam-cell ' . ($model ? $model->segmentCode : 'empty'),
			'style' => "height: {$heightPx}px;",
			'data-cidr'=>$cidr,
		]);
		
		if ($model) {
			echo $model->renderItem($this,[
				'class'=>'text-center cidr-link',
				'no_class'=>true,
				'static_view'=>true,
				'name'=>($prefix==$minPrefix)?$model->text_addr:null
			]);
		} else {
			echo Html::a($cidr, ['networks/create', 'Networks[text_addr]' => $cidr], [
				'class' => 'cidr-link',
			]);
		}
		echo Html::endTag('span');
	}
	echo Html::endTag('div');
}
echo Html::endTag('div');

$this->registerCss(<<<CSS
.ipam-wrapper {
    display: flex;
}
.ipam-column {
    display: flex;
    flex: 0 0 auto;
    width: 100px;
    flex-direction: column;
    overflow: hidden;
}
.ipam-cell {
    font-size: 11px;
    position: relative;
    overflow: hidden;
    border-radius: 0 !important;
    margin: 1px;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.ipam-cell.empty {
    background-color: #aaa;
    color: white;
}
.ipam-cell.empty:hover {
    background-color: #666;
    text-shadow: white 0 0 2px;
}
.cidr-link {
    text-decoration: none;
    color: #fff;
    font-size: 11px;
    position: sticky;
    top: 0;
    /* background: white;  чтобы не просвечивало */
    z-index: 1;
    padding: 2px;
}

.cidr-link:hover {
    color: #fff;
}

.fixed-cidr {
  position: fixed !important;
  z-index: 999;
  text-align: center;
  font-size: 11px;
}

CSS);

$this->registerJs(<<<JS
	function updateFixedLabels() {
		const viewportTop = 0;
		const viewportBottom = window.innerHeight;
		
		document.querySelectorAll('.ipam-column').forEach((column, index) => {
            //console.log(column.dataset.column??0,${minPrefix}-2);
            const prefix=column.dataset.column??32
			if (prefix<Math.min(${minPrefix}-2, ${maxPrefix}+9)) {
				const cells = column.querySelectorAll('.ipam-cell');
				let activeFoundTop = false;
				let activeFoundBottom = false;
			
				for (const cell of cells) {
					const rect = cell.getBoundingClientRect();
					const height = rect.height;
					const link = cell.querySelector('.cidr-link');
					if (!link) continue;
					
					const linkRect=link.getBoundingClientRect();
				
					// Условие: ячейка достаточно высокая и пересекает верх экрана
					if (
						height >= linkRect.height*2
						&& (
							(rect.top <= 0 && rect.bottom > linkRect.height && !activeFoundTop)
							||
							(rect.bottom >= viewportBottom && rect.top < viewportBottom-linkRect.height && !activeFoundBottom)
                        )
					) {
						link.classList.add('fixed-cidr');
						const columnRect = column.getBoundingClientRect();
						const visibleTop = Math.max(rect.top, viewportTop);
						const visibleBottom = Math.min(rect.bottom, viewportBottom);
						const top=visibleTop+(visibleBottom-visibleTop-linkRect.height)/2;
						link.style.left = columnRect.left + 'px';
						link.style.width = columnRect.width + 'px';
						link.style.top = top + 'px';
						activeFoundTop = rect.top<0;
                        activeFoundBottom = rect.bottom>visibleBottom;
					} else {
						link.classList.remove('fixed-cidr');
						link.style.top = '';
						link.style.left = '';
						link.style.width = '';
					}
				}
			}
		});
	}
	
	// Обновлять при скролле и resize
	window.addEventListener('scroll', updateFixedLabels);
	window.addEventListener('resize', updateFixedLabels);
	window.addEventListener('DOMContentLoaded', updateFixedLabels);
    updateFixedLabels();
JS);

