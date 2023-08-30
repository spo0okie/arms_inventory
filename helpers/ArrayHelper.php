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
	 * @param array $path		путь ['options','pluginOptions','multiple']
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
	 * @param array $path
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
	
	public static function explode($delimiter,$string,$trim=true,$keepEmpty=false){
		$cleaned=[];
		foreach (explode($delimiter,$string) as $item) {
			if ($trim) $item=trim($item);
			if ($keepEmpty||$item) $cleaned[]=$item;
		}
		return $cleaned;
	}
}
