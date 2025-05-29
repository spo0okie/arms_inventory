<?php

namespace app\helpers;

use yii\helpers\BaseStringHelper;
use yii\helpers\Inflector;

class StringHelper extends BaseStringHelper {
	
	/**
	 * Возвращает короткое имя класса без namespace
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
	
	//признак, что это слово исключение в множественном числе
	public static function pluralSpecial($word) {
		if (array_search(strtolower($word),array_values(Inflector::$specials))!==false)
			return true;
		return false;
	}
	
	//тоже что и в inflector, но проверяет что это не множественное слово-исключение
	public static function pluralize($word) {
		if (static::pluralSpecial($word)) return $word;
		return Inflector::pluralize($word);
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
			return lcfirst(static::pluralize(Inflector::camelize(substr($attr,0,strlen($attr)-4))));
		}
		
		return null;
	}
	
	/**
	 * Как uc_first, только для utf-8
	 * @param        $string
	 * @param string $encoding
	 * @return string
	 */
	public static function mb_ucfirst($string, $encoding='UTF-8')
	{
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, null, $encoding);
		return mb_strtoupper($firstChar, $encoding) . $then;
	}

	/**
	 * Как lc_first, только для utf-8
	 * @param        $string
	 * @param string $encoding
	 * @return string
	 */
	public static function mb_lcfirst($string, $encoding='UTF-8')
	{
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, null, $encoding);
		return mb_strtolower($firstChar, $encoding) . $then;
	}
	
	/**
	 * Делает trim, только в качестве символов для удаления можно передать массив со словами
	 * @param $string string
	 * @param $characters string|array
	 * @return string
	 */
	public static function trim(string $string,$characters) {
		if (is_string($characters)) {
			return trim($string,$characters);
		}
		
		if (!is_array($characters))
			return $string;
		
		foreach ($characters as $character) {
			$charLen=strlen($character);
			if (StringHelper::startsWith($string,$character)) {
				$string=substr($string,$charLen);
			}
			
			if (StringHelper::endsWith($string,$character)) {
				$string=substr($string,0,strlen($string)-$charLen);
			}
		}
		return $string;
	}
	
	/**
	 * Разбиваем строку в массив токенов
	 * Опционально делаем trim токенов
	 * Опционально пропускаем пустые
	 * Опционально добавляем сами разделители в токены
	 *
	 * @param string $string String to be exploded.
	 * @param string|array $delimiter Разделитель, можно передать массив вида [' ',"\t",'--']
	 * @param mixed $trim Whether to trim each element. Can be:
	 *   - boolean - to trim normally;
	 *   - string - custom characters to trim. Will be passed as a second argument to `trim()` function.
	 *   - callable - will be called for each value instead of trim. Takes the only argument - value.
	 * @param bool $skipEmpty Whether to skip empty strings between delimiters. Default is false.
	 * @param bool $keepDividers добавлять разделители в качестве токенов
	 * @return array
	 * @since 2.0.4
	 */
	public static function explode($string, $delimiter = ',', $trim = true, $skipEmpty = false, $keepDividers = false) {
		if (is_string($delimiter))
			return parent::explode($string, $delimiter, $trim, $skipEmpty);
		
		$result=[$string];
		foreach ($delimiter as $item) {
			$dLen=strlen($item);
			$exploded=[];
			foreach ($result as $token) {
				while (($pos=strpos($token,$item))!==false) {
					$exploded[]=substr($token,0,$pos);
					if ($keepDividers) $exploded[]=$item;
					$token=substr($token,$pos+$dLen);
				}
				$exploded[]=$token;
			}
			$result=$exploded;
		}
		
		if ($trim !== false) {
			if ($trim === true) {
				$trim = 'trim';
			} elseif (!is_callable($trim)) {
				$trim = function ($v) use ($trim) {
					return trim($v, $trim);
				};
			}
			$result = array_map($trim, $result);
		}
		
		if ($skipEmpty) {
			// Wrapped with array_values to make array keys sequential after empty values removing
			$result = array_values(array_filter($result, function ($value) {
				return $value !== '';
			}));
		}
		
		return $result;
	}
	
	/**
	 * Убирает суффикс из строки, если он там есть
	 * @param $string
	 * @param $suffix
	 * @return false|mixed|string
	 */
	public static function removeSuffix($string,$suffix='Recursive')
	{
		if (static::endsWith($string,$suffix))
			$string=substr($string,0,strlen($string)-strlen($suffix));
		return $string;
	}
}