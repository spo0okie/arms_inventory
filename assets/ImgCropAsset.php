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
class ImgCropAsset extends AssetBundle
{
	public $basePath = '@webroot/crop-select-js';
	public $baseUrl = '@web/crop-select-js';
	public $js = [
		'crop-select-js.min.js',
	];
	public $jsOptions = [View::POS_HEAD];
	public $css = [
		'crop-select-js.min.css',
	];
}
