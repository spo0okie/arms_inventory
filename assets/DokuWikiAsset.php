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
class DokuWikiAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
		'css/doku/wrap.css',
		'css/doku/geshi.code.css',
		'css/doku/tag.css',
		'css/doku/sxh.css',
		'css/doku/folded.css',
		'css/doku/manual.css',
    ];
    public $js = [
		'js/doku/folded.js',
    ];
	public $cssOptions = [];
	public $jsOptions = [View::POS_END];
}
