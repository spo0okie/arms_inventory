<?php


namespace app\helpers;


class ArrayHelper extends \yii\helpers\ArrayHelper
{
	/**
	 * Рекурсивно обходит древовидный массив перекрывает значения по умолчанию кастомными
	 * @param $default
	 * @param $custom
	 * @return mixed
	 */
	public static function recursiveOverride($default,$custom) {
		if (is_null($custom)) return $default;
		$default=(array)$default;
		foreach ($custom as $key=>$value) {
			if (is_array($value)) {
				$default[$key]=isset($default[$key])?
					static::recursiveOverride($default[$key],$value):
					$value;
			} else {
				$default[$key]=$value;
			}
		}
		return $default;
	}
	
	public static function findByField($array,$field,$value) {
		$result=[];
		foreach ($array as $item) {
			if (isset($item[$field]) && $item[$field]==$value)
				$result[]=$item;
		}
		return $result;
	}
}