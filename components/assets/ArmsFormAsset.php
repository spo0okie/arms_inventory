<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class ArmsFormAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/ArmsFormAsset';
	//public $css=['css/rack.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/ArmsFormAfterValidation.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
