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

//свернута по умолчанию; тогглер - клик по ссылке с data-panel-target;
//состояние запоминается на клиенте по ключу страницы
$this->registerJs(<<<JS
(function(){
	var key='docsPanel:'+location.pathname+':$panelId';
	var panel=$('#$panelId');
	if (localStorage.getItem(key)==='open') panel.show();
	$(document).on('click','a[data-panel-target="#$panelId"]',function(e){
		e.preventDefault();
		panel.slideToggle(150,function(){
			localStorage.setItem(key,panel.is(':visible')?'open':'closed');
		});
	});
})();
JS);

?>
<div class="docs-panel" id="<?= $panelId ?>" style="display: none">
	<div class="card card-body mb-3" style="max-height: <?= $maxHeight ?>; overflow-y: auto;">
		<?= $content ?>
		<div class="text-end">
			<?= Html::a('открыть в справочнике »',Url::to($moreUrl)) ?>
		</div>
	</div>
</div>
