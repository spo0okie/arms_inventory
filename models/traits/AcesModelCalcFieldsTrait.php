<?php
/**
 * Вычисляемые поля для записей доступа (ACEs)
 */

namespace app\models\traits;





use app\helpers\ArrayHelper;
use app\models\Aces;

/**
 * @package app\models\traits
 */

trait AcesModelCalcFieldsTrait
{
	static $NAME_MISSING='Пояснение отсутствует';
	
	/**
	 * Набор пользователей
	 */
	public function getDepartments()
	{
		/** @var Aces $this */
		if (!is_array($this->users)) return[];
		$departments=[];
		foreach ($this->users as $user)
			if (is_object($department=$user->orgStruct))
				$departments[$department->id]=$department;
		return $departments;
	}
	
	public function getPartners() {
		/** @var Aces $this */
		if (!count($this->users_ids)) return [];
		$partners=[];
		foreach ($this->users as $user)
			$partners[$user->org_id]=$user->org;
		return $partners;
	}
	
	
	public function hasIpAccess(){
		/** @var Aces $this */
		foreach ($this->accessTypes as $accessType) {
			if ($accessType->isIpRecursive) return true;
		}
		return false;
	}
	
	public function hasPhoneAccess(){
		/** @var Aces $this */
		foreach ($this->accessTypes as $accessType) {
			if ($accessType->isTelephonyRecursive) return true;
		}
		return false;
	}

	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		if ($this->name) return $this->name;
		return static::$NAME_MISSING;
	}
	
	/**
	 * Возвращает все субъекты досутпа одним списком
	 */
	public function getSubjects() {
		/** @var Aces $this */
		if (isset($this->attrsCache['subjects']))
			return $this->attrsCache['subjects'];
		$this->attrsCache['subjects']=[];
		foreach ($this->users as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->comps as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->services as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->netIps as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->networks as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		return $this->attrsCache['subjects'];
	}
	
	/**
	 * Возвращает все узлы субъектов доступа (сервисы разворачиваются в ОС и оборудование)
	 */
	public function getNodes() {
		/** @var Aces $this */
		if (isset($this->attrsCache['nodes']))
			return $this->attrsCache['nodes'];
		$this->attrsCache['nodes']=[];
		foreach ($this->users as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->comps as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->netIps as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->networks as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->services as $service)
			$this->attrsCache['nodes']=ArrayHelper::recursiveOverride(
				$this->attrsCache['nodes'],
				$service->nodesRecursive
			);
		return $this->attrsCache['nodes'];
		
	}
	
	public function getArchived() {
		return false;
	}
	
}