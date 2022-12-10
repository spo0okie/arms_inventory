<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class ExpandableCardWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/ExpandableCardWidgetAsset';
	public $css=['css/card.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/switch.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
