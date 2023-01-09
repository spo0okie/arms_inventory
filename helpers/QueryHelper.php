<?php


namespace app\helpers;


use yii\db\ActiveQuery;
use \yii\helpers\StringHelper;

class QueryHelper
{
	public static $stringSearchHint='Можно делать сложные запросы, используя служебные знаки:'.
	'<ul>'.
	'<li><strong>|</strong> (вертикальная черта) - ИЛИ</li>'.
	'<li><strong>&amp;</strong> (амперсанд/and) - И</li>'.
	'<li><strong>!</strong> (восклицательный зн.) - НЕ</li>'.
	'</ul>'.
	'<i>Примеры:<br />'.
	'<strong>Siemens &amp; !NX &amp; !teamcenter</strong> - Siemens, но не NX и не Teamcenter<br/>'.
	'<strong>Debian | Ubuntu</strong> - Debian или Ubuntu<br/>'.
	'</i>';
	
	public static $numberSearchHint='Можно делать сложные запросы, используя служебные знаки:'.
	'<ul>'.
	'<li><strong>|</strong> (вертикальная черта) - ИЛИ</li>'.
	'<li><strong>&amp;</strong> (амперсанд/and) - И</li>'.
	'<li><strong>&lt;</strong> (восклицательный зн.) - МЕНЬШЕ ЧЕМ</li>'.
	'<li><strong>&gt;</strong> (восклицательный зн.) - БОЛЬШЕ ЧЕМ</li>'.
	'</ul>'.
	'<i>Примеры:<br />'.
	'<strong>&lt;1000|&gt;50000</strong> - меньше 1000 или больше 50000<br/>'.
	'<strong>&gt;10000&amp;&lt;20000</strong> - больше 10000, но меньше 20000<br/>'.
	'</i>';
	
	public static $dateSearchHint='Можно делать сложные запросы, используя служебные знаки:'.
	'<ul>'.
	'<li><strong>|</strong> (вертикальная черта) - ИЛИ</li>'.
	'<li><strong>&amp;</strong> (амперсанд/and) - И</li>'.
	'<li><strong>&lt;</strong> (восклицательный зн.) - МЕНЬШЕ ЧЕМ</li>'.
	'<li><strong>&gt;</strong> (восклицательный зн.) - БОЛЬШЕ ЧЕМ</li>'.
	'</ul>'.
	'<i>Примеры:<br />'.
	'<strong>&lt;2020-01-01|&gt;2021-12-31</strong> - меньше 2020-01-01 или больше 2021-12-31<br/>'.
	'<strong>&gt;2021-05-01&amp;&lt;2021-05-09</strong> - больше 2021-05-01, но меньше 2021-05-09<br/>'.
	'</i>';
	
	private static $symbolEscapes=[
		'!'=>'###EXCLAMATION###',
		'|'=>'###BOOEANOR###',
		'&'=>'###BOOEANAND###',
		'^'=>'###LINESTART###',
		'$'=>'###LINEEND###',
	];
	
	/**
	 * Заменяет экранированные символы на служебные макросы
	 * таким образом в строке остаются только неэкранированные служебные символы
	 * @param $string
	 * @return string
	 */
	private static function escapedStringToMacro($string) {
		foreach (static::$symbolEscapes as $symbol => $macro)
			$string=str_replace('\\'.$symbol,$macro,$string);
		
		return $string;
	}
	
	/**
	 * Если в качестве параметра не строка а массив вроде [or,param1,param2],
	 * то нам надо  на входящие параметры [like,[or,param1,param2],value1]
	 * выдать [or,[like,param1,value1],[like,param2,value1]]
	 * @param $operator
	 * @param $param
	 * @param $token
	 */
	static function parseArrayInParam($operator,$param,$token) {
		//var_dump($param);
		//return '0=1';
		if (is_array($param) && count($param)>2) {
			//первый элемент - оператор объединения
			$paramOperator=reset($param);
			$condition=[$paramOperator,];
			//перебираем остальные значения массива
			while (false!==($subParam=next($param))) {
				$condition[]=[$operator,$subParam,$token,false];
			}
			return $condition;
		}
		return [$operator,$param,$token,false];
	}
	
	/**
	 * Обработка строчного токена (like или not like)
	 * @param $token
	 * @return array
	 */
	static function likeToken($token,$param) {
		if (!strlen($token)) return ['like',$param,$token];
		if (strpos($token,'!')===0) {
			$operator='not like';
			$token=trim(substr($token,1));
		} else {
			$operator='like';
		}
		if (strpos($token,'^')===0) {
			$token=substr($token,1);
		} else $token='%'.$token;
		
		if (strpos($token,'$')===strlen($token)-1) {
			$token=substr($token,0,strlen($token)-1);
		} else $token=$token.'%';
		
		//return [$operator,$param,static::macroStringToUnescape($token),false];
		return static::parseArrayInParam($operator,$param,static::macroStringToUnescape($token));
	}
	
	
	static function lessOrGreaterToken($token,$param) {
		if (substr($token,0,1)=='>') {
			$operator='>';
			$token=substr($token,1);
		} elseif (substr($token,0,1)=='<') {
			$operator='<';
			$token=substr($token,1);
		} else {
			$operator='like';
		}

		return [$operator,$param,static::macroStringToUnescape($token)];
	}
	
	/**
	 * Заменяет служебные макросы из функции выше обратно в служебные символы
	 * @param $string
	 * @return string
	 */
	private static function macroStringToUnescape($string) {
		foreach (static::$symbolEscapes as $symbol => $macro)
			$string=str_replace($macro,$symbol,$string);
		
		return $string;
	}
	
	
	public static function tokenizeString($string,$param,$tokenParser) {
		$string=static::escapedStringToMacro($string);
		
		if (strpos($string,'&')!==false) {
			$tokens=StringHelper::explode($string,'&',true,true);
			$tokensOperator='AND';
		} elseif (strpos($string,'|')!==false) {
			$tokens=StringHelper::explode($string,'|',true,true);
			$tokensOperator='OR';
		} else {
			//простой вариант
			return $tokenParser($string,$param);
		}
		
		$subQueries=[];
		
		foreach ($tokens as $token) if (strlen($token)){
			$subQueries[]=$tokenParser($token,$param);
		}
		
		return array_merge([$tokensOperator],$subQueries);
		
	}
	
	/**
	 * @param $param string
	 * @param $string string
	 * @return array
	 */
	public static function querySearchString($param,$string) {
		//var_dump(static::tokenizeString($string,$param,[static::class,'likeToken']));
		//return [];
		return static::tokenizeString($string,$param,[static::class,'likeToken']);
	}
	
	
	/**
	 * @param $param string
	 * @param $string string
	 * @return array
	 */
	public static function querySearchNumberOrDate($param,$string) {
		return static::tokenizeString($string,$param,[static::class,'lessOrGreaterToken']);
	}
}