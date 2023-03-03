<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\helpers\ArrayHelper;
use yii\base\Widget;

class UrlListWidget extends Widget
{
	/*
	 * Список УРЛов разделенный переносами строки.
	 * каждый урл может иметь описание отделенное пробелом таким образом, что последнее слово в строке - УРЛ
	 */
	public $list;
	public $ips='';
	public static $hint='Список ссылок (по одной в строке) с описанием. Последнее слово в строке - ссылка, все остальные - описание. Пример: "описание сервиса https://wiki.domain.local/services:inventory".';
	
	/**
	 * @param $item
	 * @return array
	 */
	public static function parseListItem($item) {
		$tokens=explode(' ',$item);
		if (count($tokens)>1) {
			//Есть описание
			$url=$tokens[count($tokens)-1];
			unset($tokens[count($tokens)-1]);
			$descr=implode(' ',$tokens);
		} else {
			$url=$item;
			$descr=$item;
		}
		return ['url'=>$url,'descr'=>$descr];
	}

	public function run()
	{

		$parsed=[];

		$items=explode("\n",$this->list);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$parsed[]=static::parseListItem($item);
		}

		$items=explode("\n",$this->ips);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$item=static::parseListItem('http://'.$item);
			if (!ArrayHelper::findByField($parsed,'url',$item['url']))
				$parsed[]=$item;
		}
		
		$output='';
		foreach ($parsed as $item)
			$output.=$this->render('url/item',['item'=>$item]);
			
		return $output;
	}
}