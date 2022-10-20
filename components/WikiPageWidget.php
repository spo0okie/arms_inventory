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
	
	public function run()
	{
		if (!empty(\Yii::$app->params['wikiUrl']) && !empty(\Yii::$app->params['wikiUser']) && !empty(\Yii::$app->params['wikiPass'])) {
			$items = explode("\n", $this->list);
			foreach ($items as $item) {
				$item = trim($item);
				if (!strlen($item)) continue;
				$tokens = explode(' ', $item);
				$url = $tokens[count($tokens) - 1];
				
				if (strpos($url, \Yii::$app->params['wikiUrl']) === 0) {
					$cache = \Yii::$app->cache;
					$pageName=substr($url,strlen(\Yii::$app->params['wikiUrl']));
					//пробуем выдернуть это из кэша
					//if ($page = $cache->get($url)) return '<h1>Wiki:</h1>'.$page;
					/*$arrContextOptions = [
						"http" => [
							"header" => "Authorization: Basic " . base64_encode(\Yii::$app->params['wikiUser'] . ":" . \Yii::$app->params['wikiPass']),
							'method' => 'POST',
							'content' => xmlrpc_encode_request('wiki.getPageHTML',urldecode($pageName),['encoding'=>'utf-8','escaping'=>[]]),
							'timeout' => 5,
						],
						"ssl" => [
							"verify_peer" => false,
							"verify_peer_name" => false,
						],
					];
					$page = @file_get_contents(\Yii::$app->params['wikiUrl'].'lib/exe/xmlrpc.php',
						false,
						stream_context_create($arrContextOptions)
					);
					if ($page===false) return "Ошибка получения детального описания из Wiki";
					$page=xmlrpc_decode($page);
					$page = str_replace('href="/', 'href="' . \Yii::$app->params['wikiUrl'] , $page);*/
					return '<div id="wikiPage"></div><script>$.get("'.'/web/site/wiki?pageName='.$pageName.'", function(data) {$("#wikiPage").html(data);})</script>';

					
				}
			}
		}
	}
}