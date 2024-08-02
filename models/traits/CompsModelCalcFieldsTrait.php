<?php
/**
 * Вычисляемые поля для Comps
 */

namespace app\models\traits;


use app\models\HwList;
use app\models\HwListItem;
use app\models\MaintenanceReqs;
use app\models\SwList;
use app\models\Techs;
use yii\helpers\ArrayHelper;

/**
 * @package app\models\traits
 */

trait CompsModelCalcFieldsTrait
{
	/**
	 * @return string
	 */
	public function getDomainName()
	{
		return (is_object($this->domain)?$this->domain->name:'').
			'\\'.$this->name;
	}
	
	
	public function getFqdn()
	{
		return strtolower(is_object($this->domain)?$this->name.'.'.$this->domain->fqdn:$this->name);
	}
	
	/**
	 * Возвращает отпечатки исключенного из паспорта оборудования в виде массива
	 */
	public function getExcludeHwArray()
	{
		$arrExcluded = explode("\n",$this->exclude_hw);
		foreach ($arrExcluded as $i => $item) {
			$arrExcluded[$i]=trim($item);
		}
		return $arrExcluded;
	}
	
	public function addExclusion($item)
	{
		return $this->exclude_hw=implode("\n",array_merge($this->getExcludeHwArray(),[$item]));
	}
	
	public function subExclusion($item)
	{
		return $this->exclude_hw=implode("\n",array_diff($this->getExcludeHwArray(),[$item]));
	}
	
	public function getCpuCoresCount() {
		return $this->hwList->getCpuCoresCount();
	}
	public function getRamGb() {
		return $this->hwList->getRamMb()/1024;
	}
	public function getHddGb() {
		return $this->hwList->getHddGb();
	}
	
	
	/**
	 * Возвращает все оборудование в виде HwList
	 */
	public function getHwList()
	{
		
		if (!is_null($this->hwList_obj)) return $this->hwList_obj;
		$this->hwList_obj = new HwList();
		$this->hwList_obj->loadRaw($this->raw_hw);
		return $this->hwList_obj;
	}
	
	/**
	 * Возвращает весь софт в виде SwList
	 */
	public function getSwList()
	{
		if (!is_null($this->swList_obj)) return $this->swList_obj;
		$this->swList_obj = new SwList();
		$this->swList_obj->loadItems($this->soft_ids);
		$this->swList_obj->loadRaw($this->raw_soft);
		return $this->swList_obj;
	}
	
	/**
	 * @return HwListItem[]
	 */
	public function getHardArray()
	{
		return $this->hwList->items;
	}
	
	
	/**
	 * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
	 * @param array|null $items список элементов для вывода
	 * @param string $keyField поле - ключ
	 * @param string $valueField поле - значение
	 * @param bool $asArray
	 * @return array
	 */
	public static function listItems($items=null, $keyField = 'id', $valueField = 'name', $asArray = true)
	{
		
		$query = static::find();
		if (!is_null($items)) $query->filterWhere(['id'=>$items]);
		if ($asArray) $query->select([$keyField, $valueField])->asArray();
		
		return ArrayHelper::map($query->all(), $keyField, $valueField);
	}
	
	public function getEffectiveMaintenanceReqs()
	{
		$reqs=[];
		
		foreach ($this->maintenanceReqs as $maintenanceReq) {
			$reqs[$maintenanceReq->id]=$maintenanceReq;
		}
		
		foreach ($this->services as $service) {
			foreach ($service->maintenanceReqsRecursive as $maintenanceReq) {
				$reqs[$maintenanceReq->id]=$maintenanceReq;
			}
		}
		$reqs= \app\helpers\ArrayHelper::findByField($reqs,'spread_comps',1);
		
		return MaintenanceReqs::filterEffective($reqs);
	}
	
	//список адресов, которые вернул скрипт инвентаризации
	public function getIps() {
		if (!is_null($this->ip_cache)) return $this->ip_cache;
		$this->ip_cache=explode("\n",$this->ip);
		foreach ($this->ip_cache as $i=>$ip) $this->ip_cache[$i]=trim($ip);
		$this->ip_cache=array_unique($this->ip_cache);
		return $this->ip_cache;
	}
	
	public function getSite()
	{
		return is_object($this->place)?$this->place->top:null;
	}
	
	
	public function getSegments() {
		$segments=[];
		foreach ($this->filteredIps as $ip)
			if (is_object($ip)){
				if (is_object($segment=$ip->segment))
					$segments[$segment->id]=$segment;
			}
		return $segments;
	}
	
	//фильтр наложенный пользователем
	public function getIgnoredIps() {
		if (!is_null($this->ip_ignore_cache)) return $this->ip_ignore_cache;
		$this->ip_ignore_cache=explode("\n",$this->ip_ignore);
		foreach ($this->ip_ignore_cache as $i=>$ip) $this->ip_ignore_cache[$i]=trim($ip);
		$this->ip_ignore_cache=array_unique($this->ip_ignore_cache);
		return $this->ip_ignore_cache;
	}
	
	//отфильтрованные адреса
	public function getFilteredIps() {
		if (!is_null($this->ip_filtered_cache)) return $this->ip_filtered_cache;
		$this->ip_filtered_cache=array_unique(array_diff($this->ips,$this->ignoredIps));
		return $this->ip_filtered_cache;
	}
	
	public function getFilteredIpsStr() {
		return implode(',', $this->filteredIps);
	}
	
	public function getCurrentIp() {
		if (count($this->filteredIps)) return array_values($this->filteredIps)[0];
		return '';
	}
	
	public function renderName($fqdn=false)
	{
		$suffix=is_object($this->sandbox)?$this->sandbox->suffix:'';
		return ($fqdn?mb_strtolower($this->fqdn):mb_strtoupper($this->name))
			.$suffix;
	}
	
	public function getFormattedMac() {
		
		return Techs::formatMacs($this->mac);
	}
	
}