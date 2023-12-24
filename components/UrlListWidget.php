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
use yii\helpers\Inflector;

class UrlListWidget extends Widget
{
	/*
	 * Список УРЛов разделенный переносами строки.
	 * каждый урл может иметь описание отделенное пробелом таким образом, что последнее слово в строке - УРЛ
	 */
	public $list;
	public $ips='';
	public static $hint='Список ссылок с описанием по одной в строке. <br>'
		. 'Сначала описание ссылки, затем последнее слово - сама ссылка (в ней пробелов не должно быть, вместо них %20).<br>'
		. 'Пример: <i><b>описание сервиса</b> https://wiki.domain.local/services:inventory</i>.';
	
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
		
		if ($descr==$url) {
			$descr=urldecode($url);
			//путь dokuwiki->Имя
			if (WikiPageWidget::urlIsWiki($url)) {
				//попытаемся вытащить имя из URL
				if (mb_strpos($descr,'#')!==false) {
					$descr=mb_substr($descr,mb_strpos($descr,'#')+1);
				} else {
					$tokens=explode(':',$descr);
					$descr=$tokens[count($tokens)-1];
				}
				$descr= Inflector::titleize(trim(str_replace('_',' ',$descr)));
			}
		}
		return ['url'=>trim($url),'descr'=>trim($descr)];
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