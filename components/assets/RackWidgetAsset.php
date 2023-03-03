<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class RackWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/RackWidgetAsset';
	public $css=['css/rack.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/rack-ui.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
