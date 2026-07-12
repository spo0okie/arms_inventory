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
$this->params['breadcrumbs'][] = ['label'=>app\models\Networks::$titles,'url'=>['index']];
$this->params['breadcrumbs'][] = $this->title;


/*$baseIp = '192.168.0.0'; // или получай из запроса
$maxPrefix = 16;
$minPrefix = 24;*/
$baseAddr = ip2long($baseIp);
$baseSize = 1 << (32 - $maxPrefix);
$baseEnd = $baseAddr + $baseSize - 1;
$rows=1 << ($minPrefix - $maxPrefix);
$height=20*$rows;

// Индекс: [$addr][$prefix] => модель (точное совпадение ячейки с сетью)
$map = [];
// Плоский список [addr, prefix, model], отсортированный по адресу — для поиска вложенных сетей
$nets = [];
foreach ($models as $m) {
	$prefix = $m->IPv4Block()->getPrefixLength();
	$map[$m->addr][$prefix] = $m;
	$nets[] = [$m->addr, $prefix, $m];
}
usort($nets, static function ($a, $b) {return $a[0] <=> $b[0];});

function generateBlocks(int $start, int $end, int $prefix): array {
	$step = 1 << (32 - $prefix);
	$blocks = [];
	for ($addr = $start; $addr <= $end; $addr += $step) {
		$blocks[] = [$addr, $prefix];
	}
	return $blocks;
}

/**
 * Сети, вложенные в блок $addr/$prefix (адрес внутри диапазона, префикс строго длиннее)
 * @param array $nets отсортированный по адресу список [addr, prefix, model]
 * @return \app\models\Networks[]
 */
function nestedNetworks(array $nets, int $addr, int $prefix): array {
	$end = $addr + (1 << (32 - $prefix)) - 1;
	$result = [];
	foreach ($nets as [$a, $p, $m]) {
		if ($a > $end) break;
		if ($a >= $addr && $p > $prefix) $result[] = $m;
	}
	return $result;
}

/**
 * Тултип со списком вложенных сетей (до 10 + «и ещё M»)
 * @param \app\models\Networks[] $nested
 */
function nestedTtip(array $nested): string {
	$items = [];
	foreach (array_slice($nested, 0, 10) as $n) {
		$label = $n->text_addr;
		if ($n->name) $label .= ' — ' . $n->name;
		if (is_object($n->netVlan)) $label .= ' (VLAN ' . $n->netVlan->vlan . ')';
		$items[] = Html::encode($label);
	}
	if (count($nested) > 10) $items[] = '… и ещё ' . (count($nested) - 10);
	return 'Вложенные сети (' . count($nested) . '):<br>' . implode('<br>', $items);
}

// на сколько бит углубляем детализацию при зуме и предел детализации
$zoomStep = 8;
$zoomMaxDetail = 30;

//инфопанель справки IPAM: гайд guides/ipam.md целиком; panelId совпадает
//с целью «?»-тогглера страницы (docs-panel-networks), так что иконка
//в крошках раскрывает именно её
echo \app\components\DocsPanelWidget::widget([
	'path'=>'guides/ipam.md',
	'panelId'=>'docs-panel-networks',
	'sections'=>[null,'Раскраска','Куда кликать','Параметры'],
]);

echo $this->render('ipam-form',compact('baseIp','maxPrefix','minPrefix'));

// Навигация «вверх»: карта родительского блока (маска на $zoomStep короче, детализация = текущая маска)
if ($maxPrefix > 0) {
	$parentMax = max($maxPrefix - $zoomStep, 0);
	$parentAddr = $parentMax ? ($baseAddr & ((~((1 << (32 - $parentMax)) - 1)) & 0xFFFFFFFF)) : 0;
	$parentCidr = long2ip($parentAddr) . '/' . $parentMax;
	echo Html::tag('div',
		Html::a('&uarr; Вверх: ' . $parentCidr, [
			'networks/ipam',
			'baseIp' => long2ip($parentAddr),
			'maxPrefix' => $parentMax,
			'minPrefix' => $maxPrefix,
		], [
			'class' => 'btn btn-sm btn-outline-secondary',
			'qtip_ttip' => 'Подняться на уровень выше: карта блока ' . $parentCidr
				. ' с детализацией /' . $maxPrefix,
		]),
		['class' => 'text-center mb-2']
	);
}

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
		$nested = nestedNetworks($nets, $addr, $p);
		$heightPercent = 100 / $blockCount;
		$heightPx = ($height / $blockCount) -2; //убираем отступ из высоты
		$cidr = long2ip($addr) . '/' . $p;

		// ссылка «зум»: перестроить карту с базой = адрес ячейки, маской = префикс ячейки
		$zoomUrl = ($nested && $p < $zoomMaxDetail) ? [
			'networks/ipam',
			'baseIp' => long2ip($addr),
			'maxPrefix' => $p,
			'minPrefix' => min($p + $zoomStep, $zoomMaxDetail),
		] : null;

		// класс ячейки: точное совпадение — цвет сегмента сети;
		// агрегат с единственным сегментом — его цвет; со смешанными — вертикальные
		// полосы цветов всех вложенных сегментов (ширина ~ числу сетей, см. $stripes)
		$stripes = null;
		if ($model) {
			//сеть без кода сегмента — белая (как и её доля в агрегатах)
			$cellClass = $model->segmentCode !== '' ? $model->segmentCode : 'nocode';
		} elseif ($nested) {
			$codes = []; //segmentCode => сколько вложенных сетей ('' = без кода)
			foreach ($nested as $n) $codes[$n->segmentCode] = ($codes[$n->segmentCode] ?? 0) + 1;
			if (count($codes) === 1) {
				$cellClass = key($codes) !== '' ? key($codes) : 'nocode';
			} else {
				$cellClass = 'occupied';
				$stripes = $codes;
			}
		} else {
			$cellClass = 'empty';
		}
		if ($nested) $cellClass .= ' aggregate';

		echo Html::beginTag('span', array_filter([
			'class' => 'ipam-cell ' . $cellClass,
			'style' => "height: {$heightPx}px;",
			'data-cidr'=>$cidr,
			'qtip_ttip'=>$nested ? nestedTtip($nested) : null,
		]));

		//подложка-полосы: по span на сегмент, цвет — из CSS-класса сегмента,
		//ширина пропорциональна числу вложенных сетей сегмента
		if ($stripes) {
			$parts = '';
			foreach ($stripes as $code => $cnt) {
				$parts .= Html::tag('span', '', [
					'class' => 'ipam-mix-part ' . ($code !== '' ? $code : 'nocode'),
					'style' => 'flex-grow:' . $cnt,
				]);
			}
			echo Html::tag('span', $parts, ['class' => 'ipam-mix']);
		}

		if ($model) {
			echo $model->renderItem($this,[
				'class'=>'text-center cidr-link',
				'no_class'=>true,
				'static_view'=>true,
				'name'=>($prefix==$minPrefix)?$model->text_addr:null
			]);
			if ($zoomUrl) {
				echo Html::a('+' . count($nested), $zoomUrl, ['class' => 'ipam-nested-badge']);
			}
		} elseif ($nested) {
			echo Html::a(
				$cidr . ' (' . count($nested) . ')',
				$zoomUrl ?? ['networks/ipam', 'baseIp' => long2ip($addr), 'maxPrefix' => $p, 'minPrefix' => $p],
				['class' => 'cidr-link']
			);
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
.ipam-cell.occupied {
    background-color: #8a8f98;
    color: white;
}
/* занято сетями без кода сегмента — белый (как сама такая сеть) */
.ipam-cell.nocode,
.ipam-mix-part.nocode {
    background-color: #fff;
}
.ipam-cell.nocode .cidr-link {
    color: #666;
}
/* на светлых/пёстрых подложках белую подпись выручает тёмная обводка */
.ipam-cell.aggregate .cidr-link {
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.7);
}
.ipam-cell.nocode .cidr-link {
    text-shadow: none;
}
/* штриховка агрегата — в ::before поверх подложки-полос (.ipam-mix) */
.ipam-cell.aggregate::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.18) 0,
        rgba(255, 255, 255, 0.18) 4px,
        transparent 4px,
        transparent 8px
    );
}
/* затемнение через ::after, НЕ через filter: filter создаёт stacking context
   и ломает position:fixed у плавающих подписей ячеек (уезжает адрес) */
.ipam-cell.aggregate:hover::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    background: rgba(0, 0, 0, 0.15);
    pointer-events: none;
}
.ipam-mix {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: row;
}
.ipam-mix-part {
    border-radius: 0 !important;
    flex-basis: 0;
}
.ipam-cell .cidr-link,
.ipam-cell .ipam-nested-badge {
    z-index: 2;
}
.ipam-nested-badge {
    position: absolute;
    right: 1px;
    top: 0;
    z-index: 2;
    font-size: 10px;
    line-height: 14px;
    color: #fff;
    background: rgba(0, 0, 0, 0.35);
    border-radius: 2px;
    padding: 0 3px;
    text-decoration: none;
}
.ipam-nested-badge:hover {
    color: #fff;
    background: rgba(0, 0, 0, 0.6);
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
    z-index: 1;
}

.cidr-link:hover {
    color: #fff;
}

.fixed-cidr {
  position: fixed !important;
  z-index: 999;
  text-align: center;
  overflow: hidden;
}

CSS);

$this->registerJs(<<<JS
	function updateFixedLabels() {
		const viewportTop = 0;
		const viewportBottom = window.innerHeight;

		document.querySelectorAll('.ipam-column').forEach((column, index) => {
            //console.log(column.dataset.column??0,{$minPrefix}-2);
            const prefix=column.dataset.column??32
			if (prefix<Math.min({$minPrefix}-2, {$maxPrefix}+9)) {
				const columnRect = column.getBoundingClientRect();
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
						const visibleTop = Math.max(rect.top, viewportTop);
						const visibleBottom = Math.min(rect.bottom, viewportBottom);
                        //const hiddenTop=Math.max(0,viewportTop-rect.top)
                        //const hiddenBottom=Math.max(0,rect.bottom-viewportBottom);
                        //if (hiddenTop>0&&hiddenBottom>0) console.log(hiddenTop,hiddenBottom); else console.log(rect.top,rect.bottom);
						const top=visibleTop+(visibleBottom-visibleTop-linkRect.height)*(
                            //(hiddenTop>0&&hiddenBottom>0)?
                            //(hiddenBottom)/(hiddenTop+hiddenBottom):
                            0.5
                            );
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
