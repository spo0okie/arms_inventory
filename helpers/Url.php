<?php

//Эта штука должна переопределить поведение базового хелпера УРЛ,
//задача в том что при CORS запросах мы должны отдавать абсолютный урл
//т.к. относительный будет относительно origin что ломает путь
//схема обхода уперта тут: https://stackoverflow.com/questions/48299856/yii2-make-absolutes-url-default-on-application

namespace yii\helpers;

class Url extends \yii\helpers\BaseUrl {
	
	public static $localServerCache=null;
	
	// Set the $scheme to be true by default.
	public static function to($url = '', $scheme = false) {
		
		return parent::to($url, $scheme || (!empty(\Yii::$app->request->origin) && !static::sameServer(\Yii::$app->request->origin)));
	}
	
	// Set the $scheme to be true by default.
	public static function toRoute($route, $scheme = false) {
		return parent::toRoute($route, $scheme || (!empty(\Yii::$app->request->origin) && !static::sameServer(\Yii::$app->request->origin)));
	}
	
	/**
	 * Префикс, с которого начинаются все pretty-URL приложения.
	 *
	 * Нужен клиентскому JS: статические .js-файлы не могут звать Url::to(), а
	 * писать в них адреса вида '/scans/delete' нельзя - префикс зависит от того,
	 * как приложение опубликовано ('' при каноническом DocumentRoot=web,
	 * '/web' у старых установок, '/index-test.php' в acceptance-тестах).
	 * Значение отдаётся в JS переменной armsUrlBase ({@see \app\assets\AppAsset}).
	 *
	 * @return string префикс без завершающего слэша ('' | '/web' | '/index-test.php')
	 */
	public static function appBase() {
		$manager=\Yii::$app->urlManager;
		return rtrim($manager->showScriptName?$manager->getScriptUrl():$manager->getBaseUrl(),'/');
	}
	
	/**
	 * Возвращает имя сервера из url
	 * @param $url
	 * @return mixed|string
	 */
	public static function serverName($url) {
		$schemeTokens=explode('://',$url);
		$nonScheme=$schemeTokens[1]??$url;
		return explode('/',$nonScheme)[0];
	}
	
	/**
	 * Локальный сервер приложения
	 * @return string
	 */
	public static function localServer() {
		if (is_null(static::$localServerCache)) {
			static::$localServerCache=strtolower(static::serverName(\Yii::$app->homeUrl));
		}
		return static::$localServerCache;
	}
	
	/**
	 * Проверка что URL ведет на тот же сервер что и наш
	 * @param $url
	 * @return bool
	 */
	public static function sameServer($url) {
		return (static::localServer()) == (strtolower(static::serverName($url)));
	}
	
	/**
	 * В точности как current, Но заменяет параметры не рекурсивно, что позволяет заменять параметры-массивы пустыми значениями
	 * @param array $params
	 * @param false $scheme
	 * @return string
	 */
	public static function currentNonRecursive(array $params = [], $scheme = false)
	{
		$currentParams = \Yii::$app->getRequest()->getQueryParams();
		$currentParams[0] = '/' . \Yii::$app->controller->getRoute();
		$route = array_replace($currentParams, $params);
		return static::toRoute($route, $scheme);
	}
}
