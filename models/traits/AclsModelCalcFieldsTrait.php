<?php
/**
 * Вычисляемые поля для списков доступа (ACLs)
 */

namespace app\models\traits;






use app\helpers\ArrayHelper;
use app\models\Acls;
use app\models\Places;

/**
 * @package app\models\traits
 */

trait AclsModelCalcFieldsTrait
{
	public function getName(){return $this->sname;}
	
	/**
	 * организации получающие доступ
	 * @return array
	 */
	public function getPartners() {
		/** @var Acls $this */
		if (isset($this->attrsCache['partners'])) return $this->attrsCache['partners'];
		$this->attrsCache['partners']=[];
		if (is_array($this->aces))
			foreach ($this->aces as $ace)
				$this->attrsCache['partners']= ArrayHelper::recursiveOverride(
					$this->attrsCache['partners'],
					$ace->partners
				);
		
		return $this->attrsCache['partners'];
	}
	
	/**
	 * подразделения получающие доступ
	 * @return array
	 */
	public function getDepartments() {
		/** @var Acls $this */
		if (isset($this->attrsCache['departments'])) return $this->attrsCache['departments'];
		
		$this->attrsCache['departments']=[];
		if (is_array($this->aces))
			foreach ($this->aces as $ace)
				$this->attrsCache['departments']= ArrayHelper::recursiveOverride(
					$this->attrsCache['departments'],
					$ace->departments
				);
		
		return $this->attrsCache['departments'];
	}
	
	/**
	 * Площадки расположения ресурсов
	 * @return Places[]
	 */
	public function getSites() {
		/** @var Acls $this */
		if (isset($this->attrsCache['sites'])) return $this->attrsCache['sites'];
		if (
			is_object($this->comp) &&
			is_object($this->comp->arm) &&
			is_object($this->comp->arm->place)
		) {
			$this->attrsCache['sites']=[$this->comp->arm->place->top];
		} elseif (
			is_object($this->ip) &&
			is_object($this->ip->place)
		) {
			$this->attrsCache['sites']=[$this->ip->place->top];
		} elseif (
		is_object($this->tech)
		) {
			$this->attrsCache['sites']=[$this->tech->effectivePlace];
		} elseif (is_object($this->service)) {
			$this->attrsCache['sites']=$this->service->sitesRecursive;
		} else
			$this->attrsCache['sites']=[];
		return $this->attrsCache['sites'];
	}
	
	public function getSegments() {
		/** @var Acls $this */
		if (isset($this->attrsCache['segments'])) return $this->attrsCache['segments'];
		if (is_object($this->comp)) {
			$this->attrsCache['segments']=$this->comp->segments;
		} elseif (is_object($this->ip)) {
			$this->attrsCache['segments']=[$this->ip->segment];
		} elseif (is_object($this->tech)) {
			$this->attrsCache['segments']=$this->tech->segments;
		} elseif (is_object($this->service)) {
			$this->attrsCache['segments']=[$this->service->segmentRecursive];
		} else {
			$this->attrsCache['segments']=[];
		}
		return $this->attrsCache['segments'];
	}
	
	
	
	public function getAccessTypes() {
		/** @var Acls $this */
		if (!count($this->aces)) return [];
		$types=[];
		foreach ($this->aces as $ace) {
			$types= ArrayHelper::recursiveOverride($types,$ace->accessTypes);
		}
		return $types;
	}
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		/** @var Acls $this */
		if (isset($this->attrsCache['sname'])) return $this->attrsCache['sname'];
		if (strlen($this->comment))
			$this->attrsCache['sname']=$this->comment;
		elseif (($this->comps_id) and is_object($this->comp))
			$this->attrsCache['sname']= $this->comp->renderName();
		elseif (($this->techs_id) and is_object($this->tech))
			$this->attrsCache['sname']=$this->tech->num;
		elseif (($this->services_id) and is_object($this->service))
			$this->attrsCache['sname']=$this->service->name;
		elseif (($this->ips_id) and is_object($this->ip))
			$this->attrsCache['sname']=$this->ip->sname;
		else
			$this->attrsCache['sname']=Acls::$emptyComment;
		
		return $this->attrsCache['sname'];
	}
	
	
	/**
	 * вернуть все оборудование и ОС этого сервиса
	 * @return array
	 */
	public function getNodes()
	{
		if (strlen($this->comment))
			return [$this->comment];
		
		if (($this->comps_id) and is_object($this->comp))
			return [$this->comp];
		
		if (($this->techs_id) and is_object($this->tech))
			return [$this->tech];
		
		if (($this->services_id) and is_object($this->service))
			return $this->service->getNodes();
		
		if (($this->ips_id) and is_object($this->ip))
			return [$this->ip];
		
		if (($this->networks_id) and is_object($this->network))
			return [$this->network];
		
		return [];
	}
	
	/**
	 * вернуть ресурс к которому привязан ACL
	 * @return array
	 */
	public function getResource()
	{
		if (strlen($this->comment))
			return $this->comment;
		
		if (($this->comps_id) and is_object($this->comp))
			return $this->comp;
		
		if (($this->techs_id) and is_object($this->tech))
			return $this->tech;
		
		if (($this->services_id) and is_object($this->service))
			return $this->service;
		
		if (($this->ips_id) and is_object($this->ip))
			return $this->ip;
		
		if (($this->networks_id) and is_object($this->network))
			return $this->network;
		
		return null;
	}
	
	public function hasIpAccess(){
		/** @var Acls $this */
		foreach ($this->aces as $ace) {
			if ($ace->hasIpAccess()) return true;
		}
		return false;
	}
	
	public function hasPhoneAccess(){
		/** @var Acls $this */
		foreach ($this->aces as $ace) {
			if ($ace->hasPhoneAccess()) return true;
		}
		return false;
	}
}