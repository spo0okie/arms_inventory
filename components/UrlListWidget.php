<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

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

	public function run()
	{
		$output='';

		$items=explode("\n",$this->list);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$output.=$this->render('url/item',['item'=>$item]);
		}

		$items=explode("\n",$this->ips);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$output.=$this->render('url/item',['item'=>'http://'.$item]);
		}
		return $output;
	}
}