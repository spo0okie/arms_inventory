<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

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
		'css/qtip.css',
		'css/acl.css',
		'css/arm-map.css',
		'css/arm-passport.css',
		'css/arms.css',
		'css/org-phones.css',
		'css/net-ips.css',
		'css/page-header.css',
		'css/place-map.css',
		'css/scans.css',
		'css/schedules.css',
		'css/state-colors.css',
		'css/tables.css',
		'css/codes.private.css',
		'css/bootstrap.css',
    ];
    public $js = [
		'js/scans.js',
		'js/tools.lib1.js',
	    'js/jquery.autoResize.js',
		'js/fontawesome/all.min.js',
    ];
	public $cssOptions = [];
	public $jsOptions = [View::POS_HEAD];
    public $depends = [
		'yii\web\YiiAsset',
		'yii\jui\JuiAsset',
        'yii\bootstrap5\BootstrapAsset',
		'app\assets\Select2ArmsAsset',
		'app\assets\TooltipsterAsset',
		'app\components\assets\ExpandableCardWidgetAsset'
    ];
}
