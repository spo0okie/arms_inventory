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
    	'css/custom.css',
		'css/site.css',
		'css/page-header.css',
		'css/acl.css',
		'css/schedules.css',
		'css/codes.private.css',
    ];
    public $js = [
		'js/scans.js',
		'js/tools.lib1.js',
	    'js/jquery.autoResize.js',
		'js/fontawesome/all.min.js',
    ];
    public $cssOptions = [];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
		'app\assets\Select2ArmsAsset',
		'app\assets\TooltipsterAsset',
    ];
}
