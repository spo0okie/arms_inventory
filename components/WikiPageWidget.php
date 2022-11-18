<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use yii\base\Widget;

class WikiPageWidget extends Widget
{
	/*
	 * Список УРЛов разделенный переносами строки.
	 * каждый урл может иметь описание отделенное пробелом таким образом, что последнее слово в строке - УРЛ
	 */
	public $list;
	public static $hint='Список ссылок (по одной в строке) с описанием. Последнее слово в строке - ссылка, все остальные - описание. Пример: "описание сервиса https://wiki.domain.local/services:inventory".';
	
	private static $confluencePage='/pages/viewpage.action?pageId=';
	
	public function run()
	{
		$items = explode("\n", $this->list);
		
		foreach ($items as $item) {
			$item = trim($item);
			if (!strlen($item)) continue;
			$tokens = explode(' ', $item);
			$url = $tokens[count($tokens) - 1];
			
			//DokuWiki
			if (!empty(\Yii::$app->params['wikiUrl'])
				&&
				!empty(\Yii::$app->params['wikiUser'])
				&&
				!empty(\Yii::$app->params['wikiPass'])
				&&
				strpos($url, \Yii::$app->params['wikiUrl']) === 0
			) {
				$cache = \Yii::$app->cache;
				$pageName=substr($url,strlen(\Yii::$app->params['wikiUrl']));
	
				return '<div id="wikiPage"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'.
					'<script>$.get("'.'/web/site/wiki?pageName='.$pageName.'", function(data) {$("#wikiPage").html(data);})</script>';
				
			}
			
			//Confluence
			if (!empty(\Yii::$app->params['confluenceUrl'])
				&&
				!empty(\Yii::$app->params['confluenceUser'])
				&&
				!empty(\Yii::$app->params['confluencePass'])
				&&
				strpos($url, \Yii::$app->params['confluenceUrl'].static::$confluencePage) === 0
			) {
				$cache = \Yii::$app->cache;
				$pageName=substr($url,strlen(\Yii::$app->params['confluenceUrl'].static::$confluencePage));
				
				return '<div id="wikiPage"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'.
					'<script>$.get("'.'/web/site/wiki?api=confluence&pageName='.$pageName.'", function(data) {$("#wikiPage").html(data);})</script>';
				
			}

		}
	}
}