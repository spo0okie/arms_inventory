<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\assets\DokuWikiAsset;
use app\helpers\WikiHelper;
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
	

	
	/**
	 * Из списка ссылок вытаскивает только те, которые ведут на вики в формате
	 * ['заголовок'=>'url','заголовок2'=>'url2',...]
	 * @param $list
	 * @return array
	 */
	public static function getLinks($list) {
		$items = explode("\n", $list);
		$links=[];
		//перебираем все строки
		foreach ($items as $item) {
			//разбираем строку как запись в списке ссылок
			$link=UrlListWidget::parseListItem($item);
			
			$url=$link['url'];
			$name=$link['descr'];
			
			//если это доку или конфлю, добавляем ее в вывод
			if (WikiHelper::urlIsWiki($url)) {
				//попытаемся вытащить имя из URL
				$links[$name]=$url;
			} elseif (WikiHelper::urlIsConfluence($url)) {
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
		if (WikiHelper::urlIsWiki($url)) {

			$pageName=substr($url,strlen(Yii::$app->params['wikiUrl']));
			DokuWikiAsset::register($this->view);

			return '<div id="'.$id.'" class="dokuwiki">
				<div class="spinner-border" role="status">
					<span class="visually-hidden">Loading...</span>
				</div>
			</div>
			<script>
				$.get(
                    "/web/wiki/page?pageName='.$pageName.'",
                    function(data) {
                    	$("#'.$id.'").html(data);
                    	'.(DokuWikiAsset::$dokuWikiInit).'
                    })
			</script>';
			
		}
		
		//Confluence
		if (WikiHelper::urlIsConfluence($url)) {

			$pageName=substr($url,strlen(Yii::$app->params['confluenceUrl'].WikiHelper::$confluencePage));
			
			return '<div id="'.$id.'"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'.
				'<script>$.get("/web/wiki/page?api=confluence&pageName='.$pageName.'", function(data) {$("#'.$id.'").html(data);})</script>';
			
		}
		
		return false;
	}
}