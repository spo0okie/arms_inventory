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
class Select2ArmsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
	public $js = ['select2arms/js/select2custom.js', ];
	public $css = ['select2arms/css/select2custom.css', ];
    public $jsOptions = [ 'position' => \yii\web\View::POS_HEAD ];
    public $depends = ['kartik\select2\Select2Asset',];
}
