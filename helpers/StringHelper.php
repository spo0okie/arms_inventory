<?php

namespace app\helpers;

use yii\helpers\Inflector;

class StringHelper {
	
	/**
	 * Возвращает короткое имя класса без неймспейса
	 * @param $classPath
	 * @return mixed|string
	 */
	public static function className($classPath) {
		$tokens=explode('\\',$classPath);
		return end($tokens);
	}
	
	/**
	 * Конвертирует app\modules\OrgStruct в org-struct
	 * @param $class
	 * @return string
	 */
	public static function class2Id($class) {
		return Inflector::camel2id(static::className($class));
	}
}