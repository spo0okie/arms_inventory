<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Select2 Customization asset bundle.
 */
class TooltipsterAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
	public $js = [
		'tooltipster/js/tooltipster.main.min.js',
		'tooltipster/js/tooltipster.bundle.min.js',
		'tooltipster/js/qtip_ajax.js',
	];
	public $css = [
		'tooltipster/css/tooltipster.main.min.css',
		'tooltipster/css/tooltipster.bundle.min.css',
		'tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-shadow.min.css',
		'tooltipster/css/tooltip-yellow.css',
	];
}
