<?php


namespace app\components;


use yii\bootstrap5\Tabs;
use yii\helpers\Html;

class TabsWidget extends Tabs
{
	public $cookieName='nonameTabs';
	public $defaultItem='tab1';
	public $encodeLabels=false;
	
	
	public function prepareItems(array &$items, string $prefix = '')
	{
		//что в куках записано про открытую вкладку (или берем по умолчанию)
		$cookieTab=$_COOKIE[$this->cookieName]??$this->defaultItem;
		
		$counter=0;
		$first=null;
		//$gotActive=false;
		foreach ($items as $i=>$tab) {
			if (is_null($first)) $first=$i;	//запоминаем какая была первой
			
			$counter++;
			
			// определяем ID вкладки
			$tabId='tab_'.$counter;
			if (isset($items[$i]['id'])) {
				$tabId=$items[$i]['id'];
				unset($items[$i]['id']);
			}
			
			if ($cookieTab==$tabId) {
				//$gotActive=true;
				$items[$i]['active']=true;
			}
			
			$items[$i]['options']['id']='tab-'.$tabId;
			
			//if (!isset($this->tabs[$i]['label'])) $this->tabs[$i]['label']='Tab '.$counter;
			$items[$i]['headerOptions']=['onClick'=>'document.cookie = "'.$this->cookieName.'='.$tabId.'"','id'=>'tab-'.$tabId];
		}
		
		parent::prepareItems($items, $prefix);
	}
	
	
	public static function addWikiLinks(&$tabs,$links) {
		$tabNumber=0;
		$defaultNamesCount=0;
		$wikiLinks= WikiPageWidget::getLinks($links);
		foreach ($wikiLinks as $name=>$url) {
			//идентификатор вкладки
			$tabId='wiki'.$tabNumber;
			
			//если по какой-то причине имя не распозналось и не было префикса и вернулся просто URL
			if ($name==$url) {
				//имя по умолчанию для вкладки (Wiki, Wiki #2, Wiki #3 ...)
				$name='Wiki';
				if ($defaultNamesCount++) $name.=' #'.($defaultNamesCount+1);
			}
			
			$editLink=Html::tag('i','',[
				'class'=>"fas fa-pencil-alt ps-1",
				'onClick'=>'window.open("'.$url.'?do=edit'.'","_blank");'
			]);
			
			$tabs[]=[
				'label'=>$name.$editLink,
				'id'=>$tabId,
				'content' => WikiPageWidget::Widget(['list'=>$links,'item'=>$name]),
			];
			
			$tabNumber++;
		}
	}
}