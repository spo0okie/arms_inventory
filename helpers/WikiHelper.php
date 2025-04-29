<?php

namespace app\helpers;

use Yii;
use function Symfony\Component\String\s;

class WikiHelper
{
	public const CONFLUENCE='confluence';
	public const DOKUWIKI='doku';
	
	
	public static $confluencePage='/pages/viewpage.action?pageId=';
	
	/**
	 * Проверяет, что URL - это ссылка на DokuWiki
	 * @param $url
	 * @return bool
	 */
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
	
	/**
	 * Проверяет, что URL - это ссылка на Confluence
	 * @param $url
	 * @return bool
	 */
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
	
	/**
	 * Возвращает тип вики по URL
	 * @return string
	 */
	public static function wikiUrlType($url) {
		if (static::urlIsWiki($url)) return static::DOKUWIKI;
		if (static::urlIsConfluence($url)) return static::CONFLUENCE;
		return '';
	}
	
	/**
	 * Возвращает base URL вики на основании типа вики
	 * @param $url
	 * @return string
	 */
	public static function wikiUrl($type) {
		if ($type==static::DOKUWIKI) {
			return Yii::$app->params['wikiUrl'];
		}
		if ($type==static::CONFLUENCE) {
			return Yii::$app->params['confluenceUrl'];
		}
		return '';
	}
	
	/** @noinspection PhpComposerExtensionStubsInspection */
	public static function fetchXmlRpc($method, $params)
	{
		$wikiUrl = Yii::$app->params['wikiUrl'];
	
		$arrContextOptions = [
			"http" => [
				"header" => "Authorization: Basic " . base64_encode(
					Yii::$app->params['wikiUser'] . ":" .
					Yii::$app->params['wikiPass']
				),
				'method' => 'POST',
				'content' => xmlrpc_encode_request(
					$method,
					$params,
					['encoding' => 'utf-8', 'escaping' => []]
				),
			],
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false,
			],
		];
		
		$page = @file_get_contents(
			$wikiUrl . 'lib/exe/xmlrpc.php',
			false,
			stream_context_create($arrContextOptions)
		);
		if ($page === false) return false;
		
		return xmlrpc_decode($page, 'utf-8');
	}
	
	public static function dokuwikiRender($text) {
		$wikiUrl = Yii::$app->params['wikiUrl'];
		
		$arrContextOptions = [
			"http" => [
				"header" => "Authorization: Basic " . base64_encode(
						Yii::$app->params['wikiUser'] . ":" .
						Yii::$app->params['wikiPass']
					),
				'method' => 'POST',
				'content' => $text,
			],
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false,
			],
		];
		
		return @file_get_contents(
			$wikiUrl . 'lib/exe/ajax.php?call=inventory&action=render',
			false,
			stream_context_create($arrContextOptions)
		);
	}
	
	/**
	 * Правит ссылки в html
	 * @param $html
	 * @param $wikiUrl
	 * @return array|string|string[]|null
	 */
	public static function parseWikiHtml($html, $wikiUrl) {
		
		$html = str_replace('href="/', 'href="' . $wikiUrl , $html);
		$html = str_replace('href=\'/','href=\'' . $wikiUrl , $html);
		$html = str_replace('src="/',  'src="' . $wikiUrl , $html);
		$html = str_replace('src=\'/', 'src=\'' . $wikiUrl , $html);
		$html = preg_replace_callback('|qtip_ajxhrf="/lib/exe/ajax.php\?call=inventory&action=ttip&data=([^"]+)"|',
			function($matches) {
				return 'qtip_ajxhrf="/web'.urldecode($matches[1]).'"';
			}, $html);
		
		return $html;
	}
	public static function fetchConfluence($pageName)
	{
		$wikiUrl= Yii::$app->params['confluenceUrl'];
		$arrContextOptions = [
			"http" => [
				"header" => "Authorization: Basic " . base64_encode(Yii::$app->params['confluenceUser'] . ":" . Yii::$app->params['confluencePass']),
			],"ssl" => ["verify_peer" => false,	"verify_peer_name" => false,],
		];
		$page = @file_get_contents($wikiUrl.'/rest/api/content/'.$pageName.'?expand=body.storage',
			false,
			stream_context_create($arrContextOptions)
		);
		if ($page===false) return "Ошибка получения детального описания из Wiki";
		
		$page=json_decode($page);
		if (
			!is_object($page)
			||
			!property_exists($page,'body')
			||
			!property_exists($page->body,'storage')
			||
			!property_exists($page->body->storage,'value')
		) return "Ошибка расшифровки JSON детального описания из Wiki";
		return $page->body->storage->value;
	}
	
}