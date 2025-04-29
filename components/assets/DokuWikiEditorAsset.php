<?php

namespace app\components\assets;

use yii\web\AssetBundle;

class DokuWikiEditorAsset extends AssetBundle
{
	
	public $sourcePath = __DIR__ . '/DokuWikiEditorAsset';
	public $js = [
		'js/editor.js',
	];
	
	public $css = [
		'css/editor.css',
	];
	
}