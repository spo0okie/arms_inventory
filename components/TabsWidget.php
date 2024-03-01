<?php


namespace app\components;


use yii\bootstrap5\Tabs;

class TabsWidget extends Tabs
{
	public $cookieName='nonameTabs';
	public $defaultItem='tab1';
	
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
			
			//if (!isset($this->tabs[$i]['label'])) $this->tabs[$i]['label']='Tab '.$counter;
			$items[$i]['headerOptions']=['onClick'=>'document.cookie = "'.$this->cookieName.'='.$tabId.'"','id'=>'tab-'.$tabId];
		}
		
		parent::prepareItems($items, $prefix);
	}
}