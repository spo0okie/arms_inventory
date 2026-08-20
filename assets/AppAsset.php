<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\helpers\Url;
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
		'css/markers.css',
		'css/bootstrap.css',
		'fontawesome/css/all.min.css',
		//темы оформления (plans/themes.md) — строго последним: его правила
		//должны перебивать и custom.css (5.2-сборку), и прикладные файлы
		'css/themes.css',
    ];
    public $js = [
		'js/scans.js',
		'js/tools.lib1.js',
	    'js/jquery.autoResize.js',
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

	/**
	 * {@inheritdoc}
	 *
	 * Заодно отдаём клиентскому JS префикс адресов приложения: статические
	 * скрипты (js/scans.js, js-файлы виджетов) не могут звать Url::to(), а
	 * зашитый в них '/web/...' — наследие старой схемы публикации, при
	 * каноническом DocumentRoot=web он указывал бы в никуда
	 * (docs/help/admin/install.md, {@see \yii\helpers\Url::appBase()}).
	 */
	public function registerAssetFiles($view)
	{
		$view->registerJsVar('armsUrlBase',Url::appBase());
		parent::registerAssetFiles($view);
	}
}
