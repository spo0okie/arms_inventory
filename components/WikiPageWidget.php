<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\assets\DokuWikiAsset;
use Yii;
use yii\base\Widget;

class WikiPageWidget extends Widget
{
	public static $counter=0;
	/*
	 * Список УРЛов разделенный переносами строки.
	 * каждый урл может иметь описание отделенное пробелом таким образом, что последнее слово в строке - УРЛ
	 */
	public $list;
	public $item=null; //какой найденный в списке ссылок урл выводить (если несколько ссылок на вики)
	public static $hint='Список ссылок (по одной в строке) с описанием. Последнее слово в строке - ссылка, все остальные - описание. Пример: "описание сервиса https://wiki.domain.local/services:inventory".';
	
	private static $confluencePage='/pages/viewpage.action?pageId=';
	
	public static function urlIsWiki($url) {
		return (!empty(Yii::$app->params['wikiUrl'])
			&&
			!empty(Yii::$app->params['wikiUser'])
			&&
			!empty(Yii::$app->params['wikiPass'])
			&&
			strpos($url, Yii::$app->params['wikiUrl']) === 0
		);
	}
	
	public static function urlIsConfluence($url) {
		return (!empty(Yii::$app->params['confluenceUrl'])
			&&
			!empty(Yii::$app->params['confluenceUser'])
			&&
			!empty(Yii::$app->params['confluencePass'])
			&&
			strpos($url, Yii::$app->params['confluenceUrl'].static::$confluencePage) === 0
		);
	}
	
	public static function getLinks($list) {
		$items = explode("\n", $list);
		$links=[];
		foreach ($items as $item) {
			$link=UrlListWidget::parseListItem($item);
			$url=$link['url'];
			$name=$link['descr'];
			
			if (static::urlIsWiki($url)) {
				//попытаемся вытащить имя из URL
				$links[$name]=$url;
			} elseif (static::urlIsConfluence($url)) {
				$links[$name]=$url;
			}
		}
		return $links;
	}
	
	public function run()
	{
		$links=static::getLinks($this->list);
		if (is_null($this->item))
			$url=reset($links);
		else
			$url=$links[$this->item];
		
		$id='wikiPage'.static::$counter++;
			
		//DokuWiki
		if (static::urlIsWiki($url)) {
			//$cache = Yii::$app->cache;
			$pageName=substr($url,strlen(Yii::$app->params['wikiUrl']));
			DokuWikiAsset::register($this->view);

			return '<div id="'.$id.'" class="dokuwiki"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'.
				'<script>$.get("'.'/web/site/wiki?pageName='.$pageName.'", function(data) {$("#'.$id.'").html(data);})</script>';
			
		}
		
		//Confluence
		if (static::urlIsConfluence($url)) {
			//$cache = Yii::$app->cache;
			$pageName=substr($url,strlen(Yii::$app->params['confluenceUrl'].static::$confluencePage));
			
			return '<div id="'.$id.'"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'.
				'<script>$.get("'.'/web/site/wiki?api=confluence&pageName='.$pageName.'", function(data) {$("#'.$id.'").html(data);})</script>';
			
		}
		
		return false;
	}
}