<?php
namespace app\components\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class FieldsHelperAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/FieldsHelperAsset';
	//public $css=['css/rack.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/select2hints.js'];
	public $jsOptions=['position'=>View::POS_HEAD];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
