<?php

namespace app\components\assets;

use yii\web\AssetBundle;

class DokuWikiEditorAsset extends AssetBundle
{
	
	public $sourcePath = __DIR__ . '/DokuWikiEditorAsset';

	public function init() {
		$this->js=[
			['js/buttons.js',['position' => \yii\web\View::POS_BEGIN]],
			\Yii::$app->params['wikiUrl'].'lib/scripts/helpers.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/edit.js',
			//это какой-то мастер ссылок, я его в buttons.js заменил на вставку шаблона ссылки
			//там и так все тривиально чтобы еще мастер использовать
			//\Yii::$app->params['wikiUrl'].'lib/scripts/linkwiz.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/textselection.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/toolbar.js',
			\Yii::$app->params['wikiUrl'].'lib/scripts/script.js',
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