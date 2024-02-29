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
	
	/**
	 * Конвертирует acl_list_ids => aclList
	 * если нет суффиксов _id / _ids, то возвращает NULL
	 * @param $attr
	 * @return string
	 */
	public static function linkId2Getter($attr) {
		if (substr($attr,strlen($attr)-3)=='_id') {
			return lcfirst(Inflector::singularize(Inflector::camelize(substr($attr,0,strlen($attr)-3))));
		}
		
		if (substr($attr,strlen($attr)-4)=='_ids') {
			return lcfirst(Inflector::camelize(substr($attr,0,strlen($attr)-4)));
		}
		
		return null;
	}
}