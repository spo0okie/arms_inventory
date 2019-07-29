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
	public static $hint='Список связанных ссылок (по одной в строке). Можно перед ссылкой добавить ее описание, отделив его от ссылки пробелом (последнее слово в строке - ссылка)';

	public function run()
	{
		$output='';

		$items=explode("\n",$this->list);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$output.=$this->render('url/item',['item'=>$item]);
		}
		return $output;
	}
}