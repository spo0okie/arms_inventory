<?php
namespace app\components\Forms\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class ArmsFormAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/ArmsFormAsset';
	public $js=['js/ArmsFormAfterValidation.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
