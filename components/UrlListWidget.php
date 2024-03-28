<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\helpers\ArrayHelper;
use Yii;
use yii\base\Widget;
use yii\helpers\Inflector;

class UrlListWidget extends Widget
{
	/*
	 * Список УРЛов разделенный переносами строки.
	 * каждый урл может иметь описание отделенное пробелом таким образом, что последнее слово в строке - УРЛ
	 */
	public $list;
	public $parsed;
	public $rendered;
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
				//выкусываем из описания УРЛ к вики, чтобы остался только путь до документа
				$descr=mb_substr($descr,mb_strlen(urldecode(Yii::$app->params['wikiUrl'])));
				
				//попытаемся вытащить имя из пути до документа
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
	
	public function parse()
	{
		if (isset($this->parsed)) return;
		$this->parsed=[];
		
		$items=explode("\n",$this->list);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$this->parsed[]=static::parseListItem($item);
		}
		
		$items=explode("\n",$this->ips);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$item=static::parseListItem('http://'.$item);
			if (!ArrayHelper::findByField($this->parsed,'url',$item['url']))
				$this->parsed[]=$item;
		}
	}
	
	public function renderItems()
	{
		if (isset($this->rendered)) return;
		
		$this->parse();
		
		$this->rendered=[];
		foreach ($this->parsed as $item)
			$this->rendered[]=$this->render('url/item',['item'=>$item]);
	}

	public function run()
	{
		$this->renderItems();
		
		return implode($this->rendered);
	}
}