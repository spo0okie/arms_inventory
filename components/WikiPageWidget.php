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
					//пробуем выдернуть это из кэша
					if ($page = $cache->get($url)) return '<h1>Wiki:</h1>'.$page;
					$arrContextOptions = [
						"http" => [
							"header" => "Authorization: Basic " . base64_encode(\Yii::$app->params['wikiUser'] . ":" . \Yii::$app->params['wikiPass'])
						],
						"ssl" => [
							"verify_peer" => false,
							"verify_peer_name" => false,
						],
						'timeout' => 5
					];
					$page = @file_get_contents($url, false, stream_context_create($arrContextOptions));
					if ($page===false) return "Ошибка получения детального описания из Wiki";
					$startCode = '<div class="dw-content">';
					$endCode = '<div class="comment_wrapper" id="comment_wrapper">';
					if ($startPos = strpos($page, $startCode)) {
						$page = substr($page, $startPos + strlen($startCode));
						if ($endPos = strpos($page, $endCode)) {
							$page = substr($page, 0, $endPos);
							if ($titlePos = strpos($page, '</h1>')) {
								$page = substr($page, $titlePos + 5);
							}
							
							$page = str_replace('href="/', 'href="' . \Yii::$app->params['wikiUrl'] , $page);
							$cache->set($url, $page, 3600*12);
							
							return '<h1>Wiki:</h1>'. $page;
						} else return "no end";
					} else return "no start";
					
				}
			}
		}
	}
}