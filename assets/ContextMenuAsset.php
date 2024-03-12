<?php
/**
 * загружаем JS плагин js-crop
 * @link https://github.com/zara-4/crop-select-js
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Select2 Customization asset bundle.
 */
class ContextMenuAsset extends AssetBundle
{
    public $basePath = '@webroot/contextMenu';
	public $baseUrl = '@web/contextMenu';
	public $js = [
		'jquery.contextMenu.min.js',
		'jquery.ui.position.min.js'
	];
	public $jsOptions = [View::POS_HEAD];
	public $css = [
		'jquery.contextMenu.min.css',
	];
}
