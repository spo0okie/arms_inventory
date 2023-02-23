<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class CollapsableCardWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/CollapsableCardWidgetAsset';
	public $css=['css/card.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/collapsable.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
