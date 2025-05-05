<?php
namespace app\components\Forms\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class Select2FieldAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/Select2FieldAsset';
	public $js=['js/select2hints.js'];
	public $jsOptions=['position'=>View::POS_HEAD];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
