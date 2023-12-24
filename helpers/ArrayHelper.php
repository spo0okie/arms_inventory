<?php


namespace app\helpers;


class ArrayHelper extends \yii\helpers\ArrayHelper
{
	/**
	 * Рекурсивно обходит древовидный массив и перекрывает значения по умолчанию кастомными
	 * @param $default array
	 * @param $custom array
	 * @return array
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
	
	/**
	 * Возвращает из массива элементов те, у которых field==value
	 * @param $array
	 * @param $field
	 * @param $value
	 * @return array
	 */
	public static function findByField($array,$field,$value) {
		$result=[];
		foreach ($array as $item) {
			if (
				(isset($item[$field]) && $item[$field]==$value)
				||
				(is_object($item)&&property_exists($item,$field) && $item->$field==$value)
			)
				$result[]=$item;
		}
		return $result;
	}
	
	/**
	 * Из переданного массива элементов удаляет те, у которых field==value
	 * @param $array
	 * @param $field
	 * @param $value
	 */
	public static function deleteByField(&$array,$field,$value) {
		foreach ($array as $key=>$item) {
			if (
				(isset($item[$field]) && $item[$field]==$value)
				||
				(is_object($item)&&property_exists($item,$field) && $item->$field==$value)
			)
				unset($array[$key]);
		}
	}
	
	/**
	 * Возвращает значение из древовидного архива
	 * или $default если значения нет
	 * @param array $array		исходный архив в котором ищем
	 * @param array|string $path		путь ['options','pluginOptions','multiple']
	 * @param null $default		значение если ничего не нашлось
	 * @return mixed|null
	 */
	public static function getTreeValue($array,$path,$default=null) {
		if (!is_array($path)) $path=explode('/',$path);
		//движемся от корня пути к концу
		for ($i=0;$i<count($path);$i++) {
			//на каждом этапе проверяем есть ли нужный элемент (директория/узел дерева)
			if (!isset($array[$path[$i]])) return $default; //если нет - отступаем на $default
			//иначе делаем chdir
			$array=$array[$path[$i]];
		}
		//если дошли до конца пути - возвращаем результат
		return $array;
	}
	
	/**
	 * Строит из пути и значения (['options','pluginOptions','multiple'],true)
	 * ветку вида ['options'=>['pluginOptions'=>['multiple'=>true]]]
	 * @param array|string $path
	 * @param mixed $value
	 * @return array
	 */
	public static function buildBranch($path,$value) {
		if (!is_array($path)) $path=explode('/',$path);
		//движемся от корня пути к концу
		$newBranch=[$path[count($path)-1]=>$value];
		
		for ($i=count($path)-2;$i>=0;$i--) {
			$newBranch=[$path[$i]=>$newBranch];
		}
		
		return $newBranch;
	}
	
	/**
	 * Записывает значение в древовидный архив
	 * @param array $array		исходный архив в котором ищем
	 * @param array $path		путь ['options','pluginOptions','multiple']
	 * @param mixed $value		значение если ничего не нашлось
	 * @return mixed|null
	 */
	public static function setTreeValue($array,$path,$value) {
		//если дошли до конца пути - возвращаем результат
		return self::recursiveOverride( //переписываем
			$array,						//исходный архив
			self::buildBranch(			//веткой построенной
				$path,					//из пути
				$value					//и значения
			)
		);
	}

	/**
	 * Записывает значение по умолчанию в древовидный архив (если не установлено)
	 * @param array $array		исходный архив в котором ищем
	 * @param array $path		путь ['options','pluginOptions','multiple']
	 * @param mixed $value		значение если ничего не нашлось
	 * @return mixed|null
	 */
	public static function setTreeDefaultValue($array,$path,$value) {
		//если дошли до конца пути - возвращаем результат
		return self::recursiveOverride( //переписываем
			self::buildBranch(			//ветку построенную
				$path,					//из пути
				$value					//и значения по умолчанию
			),
			$array						//исходным архивом
		);
	}
	
	public static function implode($glue,$array,$keepEmpty=false){
		$cleaned=[];
		foreach ($array as $item) if ($keepEmpty||$item) $cleaned[]=$item;
		return implode($glue,$cleaned);
	}
	
	/**
	 * Split a string by a string.
	 *
	 * @link https://php.net/manual/en/function.explode.php
	 *
	 * @param string   $separator Разделитель
	 * @param string   $string Чего разделить
	 * @param int|null $limit Если аргумент limit является положительным, возвращаемый массив будет содержать
	 * максимум limit элементов, при этом последний элемент будет содержать остаток строки string.
	 * Если параметр limit отрицателен, то будут возвращены все компоненты, кроме последних -limit.
	 * Если limit равен нулю, то он расценивается как 1.
	 *
	 * @param bool     $trim
	 * @param bool     $keepEmpty
	 * @return string[] Если separator является пустой строкой (""), explode() выбрасывает ValueError.
	 * Если separator не содержится в string, и используется отрицательный limit, то будет возвращён пустой массив (array),
	 * иначе будет возвращён массив, содержащий string.
	 * Если значения separator появляются в начале или в конце string, указанные значения будут добавлены как пустое
	 * значение массива (array), либо в первой, либо в последней позиции возвращённого массива (array) соответственно.
	 */
	public static function explode(string $separator, string $string, $limit=null, $trim=true, $keepEmpty=false){
		if (!isset($string) || !isset($separator)) {
			return [];
		}
		$separator = (string)$separator;
		$string = (string)$string;
		$cleaned=[];
		foreach (isset($limit) ? explode($separator, $string, $limit) : explode($separator, $string) as $item) {
			if ($trim) $item=trim($item);
			if ($keepEmpty||$item) $cleaned[]=$item;
		}
		return $cleaned;
	}
	
	/**
	 * Переключает присутствие элемента в массиве (если не было - добавит, иначе уберет
	 * @param $array
	 * @param $item
	 * @return array
	 */
	public static function itemToggle($array,$item) {
		$pos=array_search($item,$array);
		if ($pos!==false) {
			unset($array[$pos]);
			return $array;
		}
		$array[]=$item;
		return $array;
	}
	
	/**
	 * Получить свойство объекта или поле массива
	 * @param      $obj
	 * @param      $field
	 * @param null $default
	 * @return mixed|null
	 */
	public static function getField($obj,$field,$default=null) {
		if (is_object($obj)) {
			return $obj->$field;
		}
		if (is_array($obj)) {
			if (isset($obj[$field]))
				return $obj[$field];
		}
		return $default;
	}
	
	/**
	 * Как и выше, только на вход подаем массив объектов на выходе получаем массив значений поля $field
	 * @param array  $objArray
	 * @param string $field
	 * @param null   $default
	 * @return array
	 */
	public static function getArrayField(array $objArray, string $field, $default=null) {
		$result=[];
		foreach ($objArray as $obj) $result[]=static::getField($obj,$field,$default);
		return $result;
	}
	
	/**
	 * Проверяет что у $item все поля соответствуют фильтру [field1=>value1,field2=>value2]
	 * @param $item
	 * @param $filter
	 * @return bool
	 */
	public static function compareItemFields($item,$filter) {
		foreach ((array)$filter as $var => $value) {
			$testValue=static::getField($item,$var);
			if (is_array($testValue) || is_object($testValue)) {
				if (!static::compareItemFields($testValue,$value)) return false;
			} else {
				if ($testValue != $value) {
					//echo "$var: $testValue != $value\n";
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Поиск элементов массива содержащих в себе набор $search[ключ1=>значение1,...]
	 * @param $array
	 * @param $search
	 * @return array
	 */
	public static function getItemsByFields($array,$search) {
		$result=[];
		foreach ($array as $item) {
			if (static::compareItemFields($item,$search)) $result[]=$item;
		}
		return $result;
	}
	
	
}
