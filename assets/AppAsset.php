<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
		'css/site.css',
		'css/codes.private.css',
	    'css/tooltip-yellow.css',
	    'css/tooltipster.main.min.css',
	    'css/tooltipster.bundle.min.css',
	    'css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-shadow.min.css',
    ];
    public $js = [
	    //'js/qtip2.js',
	    'js/tooltipster.main.min.js',
	    'js/tooltipster.bundle.min.js',
	    'js/qtip_ajax.js',
		'js/scans.js',
		'js/tools.lib1.js',
	    'js/jquery.autoResize.js',
    ];
    public $cssOptions = [];
    public $depends = [
	    'rmrevin\yii\fontawesome\CdnFreeAssetBundle',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
