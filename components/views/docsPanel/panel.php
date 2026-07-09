<?php

use app\components\assets\MermaidAsset;
use yii\helpers\Html;
use yii\helpers\Url;

MermaidAsset::register($this);

/* @var $this yii\web\View */
/* @var $panelId string id панели (тогглер - ссылка с data-panel-target, обычно HintIconWidget) */
/* @var $content string готовый HTML фрагментов документации */
/* @var $maxHeight string ограничение высоты с внутренним скроллом */
/* @var $moreUrl array маршрут полной страницы справочника */

//Панель свёрнута по умолчанию (display:none). Тогглер и режим справки
//(help-mode) обрабатываются ЕДИНЫМ глобальным скриптом в layout
//(views/layouts/main.php): клик по иконке с data-panel-target всегда
//переключает подсветку атрибутов, а панель показывает/прячет, если она
//есть на странице. Здесь — только разметка панели.
?>
<div class="docs-panel" id="<?= $panelId ?>" style="display: none">
	<div class="card card-body mb-3" style="max-height: <?= $maxHeight ?>; overflow-y: auto;">
		<?= $content ?>
		<div class="text-end">
			<?= Html::a('открыть в справочнике »',Url::to($moreUrl)) ?>
		</div>
	</div>
</div>
