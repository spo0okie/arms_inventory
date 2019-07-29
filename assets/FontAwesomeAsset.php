<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 25.03.2019
 * Time: 10:59
 */

namespace app\assets;

use yii\web\AssetBundle;

class FontAwesomeAsset extends AssetBundle
{
	public $sourcePath = '@bower/font-awesome';
	public $css = [
		'css/fontawesome.min.css',
	];
}