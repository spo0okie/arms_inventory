<?php


namespace app\helpers;


use yii\db\ActiveQuery;

class QueryHelper
{
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
	
	static function likeToken($token) {
		if (!strlen($token)) return ['like',$token];
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

		return [$operator,$token];
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
	
	/**
	 * @param $param string
	 * @param $string string
	 * @return array
	 */
	public static function querySearchString($param,$string) {

		$string=static::escapedStringToMacro($string);

		if (strpos($string,'&')!==false) {
			$tokens=\yii\helpers\StringHelper::explode($string,'&',true,true);
			$tokensOperator='AND';
		} elseif (strpos($string,'|')!==false) {
			$tokens=\yii\helpers\StringHelper::explode($string,'|',true,true);
			$tokensOperator='OR';
		} else {
			//простой вариант
			list($operator,$string)=static::likeToken($string);
			return [$operator,$param,$string,false];
		}

		$subQueries=[];

		//var_dump($tokens);
		foreach ($tokens as $token) if (strlen($token)){
			list($operator,$token)=static::likeToken($token);
			$subQueries[]=[$operator,$param,$token,false];
		}
		
		return array_merge([$tokensOperator],$subQueries);
	}
}