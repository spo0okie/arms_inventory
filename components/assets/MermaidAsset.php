<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * mermaid.js для рендера ```mermaid``` диаграмм во встроенной документации
 * (plans/help-content-review.md). Вендорено локально (не CDN — политика
 * самодостаточности проекта). GitHub рендерит mermaid-блоки нативно,
 * в приложении — эта библиотека. Подключается на страницах docs
 * (views/docs/page.php, model.php) и в DocsPanelWidget.
 */
class MermaidAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/MermaidAsset';
	public $js = [
		'js/mermaid.min.js',
		'js/mermaid-init.js',	//порядок важен: init после библиотеки
	];
	public $jsOptions = ['position' => View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
