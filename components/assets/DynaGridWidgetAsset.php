<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class DynaGridWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/DynaGridWidgetAsset';
	public $css=['css/table.css'];
	public $js=['js/resize-columns.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
