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
	public $sourcePath = '@bower/fontawesome';
	public $css = [
		'css/fontawesome.min.css',
	];
	public $js = [
		'js/all.min.js',
	];
	public $publishOptions = [
		'only' => [
			'fonts/*',
			'css/*',
			'js/*',
			'svgs/*',
			'webfonts/*',
			'otfs/*',
		],
		'forceCopy' => YII_DEBUG,
	];
}