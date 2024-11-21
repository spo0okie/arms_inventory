<?php
/**
 * Вычисляемые поля для Сервисов
 */

namespace app\models\traits;




use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use app\models\Contracts;
use app\models\MaintenanceReqs;
use app\models\Scans;
use app\models\Schedules;
use app\models\Services;
use app\models\Users;

/**
 * @package app\models\traits

 * @property string $name
 * @property Scans $preview
 * @property Users[] $supportTeam
 */

trait ServicesModelCalcFieldsTrait
{
	/**
	 * Заголовок конкретно этой модели (сервис/услуга)
	 * @return string
	 */
	public function getShortTitle() {
		return $this->is_service?Services::$service_title:Services::$job_title;
	}
	
	/**
	 * @return string
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getInfrastructureResponsibleName() {
		if (is_object($this->infrastructureResponsibleRecursive)) return $this->infrastructureResponsibleRecursive->Ename;
		//если никто не нашелся за инфраструктуру, тогда за нее отвечает ответственный за сервис
		return $this->responsibleName;
	}
	
	public function getInfrastructureResponsibleRecursive() {
		/** @var Services $this */
		return $this->findRecursiveAttr(
			'infrastructureResponsible',
			'infrastructureResponsibleRecursive',
			'parentService'
		);
	}
	
	public function getInfrastructureSupportRecursive() {
		/** @var Services $this */
		return $this->findRecursiveAttr(
			'infrastructureSupport',
			'infrastructureSupportRecursive',
			'parentService',
			[]
		);
	}
	
	public function getInfrastructureSupportNames() {
		$names=[];
		foreach ($this->infrastructureSupportRecursive as $user)
			if (is_object($user)) $names[]=$user->Ename;
		
		if (count($names)) return implode(',',$names);
		//если никто не нашелся за инфраструктуру, тогда за нее отвечает ответственный за сервис
		return $this->supportNames;
	}
	
	public function getProvidingScheduleName() {
		if (is_object($this->providingScheduleRecursive)) return $this->providingScheduleRecursive->name;
		return null;
	}
	
	/**
	 * @return Schedules|null
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getProvidingScheduleRecursive() {
		return $this->findRecursiveAttr(
			'providingSchedule',
			'providingScheduleRecursive',
			'parentService'
		);
	}
	
	public function getResponsibleName() {
		if (is_object($this->responsibleRecursive)) return $this->responsibleRecursive->Ename;
		return null;
	}
	
	public function getResponsibleRecursive() {
		return $this->findRecursiveAttr(
			'responsible',
			'responsibleRecursive',
			'parentService'
		);
	}
	
	public function getLinksRecursive() {
		return $this->findRecursiveAttr(
			'links',
			'linksRecursive',
			'parentService'
		);
	}
	
	/**
	 * Возвращает сегмент сервиса с учетом родителей
	 * @return string|null
	 */
	public function getSegmentRecursive() {
		return $this->findRecursiveAttr(
			'segment',
			'segmentRecursive',
			'parentService'
		);
	}
	
	public function getSegmentName() {
		if (is_object($this->segmentRecursive)) return $this->segmentRecursive->name;
		return null;
	}
	
	public function getSumTotals()
	{
		if ($this->cost) return $this->cost;
		if (isset($this->attrsCache['sumCharge'])) return $this->attrsCache['sumCharge'];
		$this->attrsCache['sumCharge']=0;
		foreach ($this->orgPhones as $phone)	$this->attrsCache['sumCharge']+=$phone->cost;
		foreach ($this->orgInets as $inet)		$this->attrsCache['sumCharge']+=$inet->cost;
		foreach ($this->children as $service)	$this->attrsCache['sumCharge']+=$service->sumTotals;
		return $this->attrsCache['sumCharge'];
	}
	
	public function getSumCharge()
	{
		/** @var Services $this */
		if ($this->charge) return $this->charge;
		if (isset($this->attrsCache['sumTotals'])) return $this->attrsCache['sumTotals'];
		$this->attrsCache['sumTotals']=0;
		foreach ($this->orgPhones as $phone)	$this->attrsCache['sumTotals']+=$phone->charge;
		foreach ($this->orgInets as $inet)		$this->attrsCache['sumTotals']+=$inet->charge;
		foreach ($this->children as $service)	$this->attrsCache['sumTotals']+=$service->sumCharge;
		return $this->attrsCache['sumTotals'];
	}
	
	public function getSupportRecursive() {
		return $this->findRecursiveAttr(
			'support',
			'supportRecursive',
			'parentService',
			[]
		);
	}
	
	public function getSupportNames() {
		$names=[];
		foreach ($this->supportRecursive as $user)
			if (is_object($user)) $names[]=$user->Ename;
		
		if (count($names)) return implode(',',$names);
		return null;
	}
	
	public function getSupportScheduleName() {
		if (is_object($this->supportScheduleRecursive)) return $this->supportScheduleRecursive->name;
		return null;
	}
	
	public function getSupportScheduleRecursive() {
		return $this->findRecursiveAttr(
			'supportSchedule',
			'supportScheduleRecursive',
			'parentService'
		);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис (и дочерние)
	 */
	public function getCompsRecursive()
	{
		if (!isset($this->attrsCache['compsRecursive'])) {
			$this->attrsCache['compsRecursive']=[];
			foreach ($this->comps as $comp)
				$this->attrsCache['compsRecursive'][$comp->id]=$comp;
			
			foreach ($this->children as $child)
				$this->attrsCache['compsRecursive']=ArrayHelper::recursiveOverride($child->compsRecursive,$this->attrsCache['compsRecursive']);
		}
		return $this->attrsCache['compsRecursive'];
	}
	
	
	
	/**
	 * Возвращает оборудование на котором живет этот сервис (и дочерние)
	 */
	public function getTechsRecursive()
	{
		if (!isset($this->attrsCache['techsRecursive'])) {
			$this->attrsCache['techsRecursive']=[];
			foreach ($this->techs as $tech)
				$this->attrsCache['techsRecursive'][$tech->id]=$tech;
			
			foreach ($this->children as $child)
				$this->attrsCache['techsRecursive']=ArrayHelper::recursiveOverride($child->techsRecursive,$this->attrsCache['techsRecursive']);
		}
		return $this->attrsCache['techsRecursive'];
	}
	
	
	
	/**
	 * Список привязанных к сервису документов
	 * @return Contracts[]
	 */
	public function getDocs()
	{
		if (isset($this->attrsCache['docs'])) return $this->attrsCache['docs'];
		
		$this->attrsCache['docs']=[];
		foreach ($this->contracts as $contract) {
			$this->attrsCache['docs'][]=$contract;
			/**
			 * @var $contract Contracts
			 */
			foreach ($contract->allChildren as $child) {
				$this->attrsCache['docs'][]=$child;
			}
		}
		return $this->attrsCache['docs'];
	}
	
	/**
	 * Список платежных документов
	 * @return Contracts[]
	 */
	public function getPayments()
	{
		$payments=[];
		foreach ($this->docs as $doc) {
			/**
			 * @var $doc Contracts
			 */
			if ($doc->total) $payments[]=$doc;
		}
		return $payments;
	}
	
	
	/**
	 * Сумма неоплаченных документов
	 * @return array
	 */
	public function getTotalUnpaid()
	{
		$total=[];
		foreach ($this->payments as $doc)
			/**
			 * @var $doc Contracts
			 */
			if ($doc->isUnpaid) {
				$currency=$doc->currency->symbol;
				if (!isset($total[$currency])) $total[$currency]=0;
				$total[$currency]+=$doc->total;
			}
		
		return $total;
	}
	
	/**
	 * Сумма неоплаченных документов
	 * @return int
	 */
	public function getFirstUnpaid()
	{
		$iFirst=0;
		$strFirst=null;
		foreach ($this->payments as $doc)
			/** @var $doc Contracts */
			if ($doc->isUnpaid) {
				if (!$iFirst || strtotime($doc->date)<$iFirst) {
					$strFirst=$doc->date;
					$iFirst=strtotime($strFirst);
				}
			}
		
		return $strFirst;
	}
	
	public function getMaintenanceReqsRecursive()
	{
		if (isset($this->attrsCache['maintenanceReqsRecursive'])) return $this->attrsCache['maintenanceReqsRecursive'];
		return $this->attrsCache['maintenanceReqsRecursive']=MaintenanceReqs::filterEffective(
			$this->findRecursiveAttr('maintenanceReqs','maintenanceReqsRecursive','parentService', [])
		);
	}
	
	public function getBackupReqs()
	{
		return ArrayHelper::getItemsByFields($this->maintenanceReqsRecursive??[],['is_backup'=>1]);
	}
	
	public function getOtherReqs()
	{
		return ArrayHelper::getItemsByFields($this->maintenanceReqsRecursive??[],['is_backup'=>0]);
	}
	
	/**
	 * Имя с откушенным родителем
	 * @param string $name
	 * @return string
	 */
	public function getNameWithoutParent($name='')
	{
		if (!strlen($name)) $name = $this->name;
		$dividers = ['->', '::', '-', ':',  '/', '\\', '>', ' '];
		if (is_object($this->parentService)) {
			//разбиваем имя на слова
			$tokens = StringHelper::explode($name, ' ', true, true);
			//убираем разделители
			
			foreach ($this->parentService->getAliases() as $alias) {
				//разбиваем альяс на слова
				$aliasTokens = StringHelper::explode($alias, ' ', true, true);
				//если слова альяса это первые слова имени
				$aliasName=StringHelper::trim(mb_strtolower(implode($aliasTokens)),$dividers);
				$testName=StringHelper::trim(mb_strtolower(implode(array_slice($tokens, 0,count($aliasTokens)))),$dividers);
				if ($testName==$aliasName) {
					//собственно нашли совпадение
					
					//убираем альяс из начала имени (откусываем в качестве имени правый набор слов после альяса)
					$tokens = array_slice($tokens, count($aliasTokens));
					
					//если теперь в начале имени стоит разделитель, его бы убрать
					if (array_search($tokens[0], $dividers) !== false)
						array_shift($tokens);
					
					$name = implode(' ', $tokens);
					break;
				}
			}
		}
		return $name;
	}
	
	/**
	 * Вернуть все узлы входящие в сервис
	 * @return array
	 */
	public function getNodes() {
		$items=[];
		foreach ($this->comps as $comp)
			$items[$comp->uuid()]=$comp;
		foreach ($this->techs as $tech)
			$items[$tech->uuid()]=$tech;
		return $items;
	}
	
	/**
	 * Вернуть все узлы входящие в сервис
	 * @return array
	 */
	public function getNodesRecursive() {
		$items=[];
		foreach ($this->compsRecursive as $comp)
			$items[$comp->uuid()]=$comp;
		foreach ($this->techsRecursive as $tech)
			$items[$tech->uuid()]=$tech;
		return $items;
	}
}