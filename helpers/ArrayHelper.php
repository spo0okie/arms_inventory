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
		foreach ($custom as $key=>$value) {
			if (!isset($default[$key]))
				$default[$key]=$value;
			elseif (is_array($value) && is_array($default[$key]))
				$default[$key]=static::recursiveOverride($default[$key],$value);
		}
		return $default;
	}
}