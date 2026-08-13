<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* CornerWidget asset bundle.
*/
class CornerWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/CornerWidgetAsset';
	public $css=['css/corner.css'];
	public $js=['js/corner.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
