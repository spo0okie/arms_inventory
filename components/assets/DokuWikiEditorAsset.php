<?php

namespace app\components\assets;

use yii\web\AssetBundle;

class DokuWikiEditorAsset extends AssetBundle
{
	
	public $sourcePath = __DIR__ . '/DokuWikiEditorAsset';
	public $css = [
		'css/editor.css',
	];
	public function init() {
		$this->js=[
			['js/buttons.js',['position' => \yii\web\View::POS_HEAD]],
			\Yii::$app->params['wikiUrl'].'lib/scripts/helpers.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/edit.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/linkwiz.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/toolbar.js',
		];
	}
	
	public $depends = [
		'yii\jui\JuiAsset',
		'app\assets\DokuWikiAsset',
	];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}