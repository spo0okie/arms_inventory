<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;




use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Scans;
use app\models\Users;

/**
 * @package app\models\traits

 * @property string $name
 * @property Scans $preview
 * @property Users[] $supportTeam
 */

trait MaintenanceJobsModelCalcFieldsTrait
{
	public function getResponsible()
	{
		if (is_object($this->serviceRecursive)) return $this->serviceRecursive->responsibleRecursive;
		return null;
	}
	
	public function getSupport()
	{
		if (is_object($this->serviceRecursive)) return $this->serviceRecursive->supportRecursive;
		return null;
	}
	
	/**
	 * Признак того, что это бэкап (удовлетворяет требованию, которое бэкап)
	 */
	public function getIsBackup()
	{
		if (!isset($this->attrsCache['isBackup'])) {
			$this->attrsCache['isBackup']=false;
			foreach ($this->reqs as $req) {
				if ($req->is_backup) {
					$this->attrsCache['isBackup']=true;
					break;
				}
			}
		}
		return $this->attrsCache['isBackup'];
	}
	
	public function getServiceRecursive()
	{
		/** @var MaintenanceJobs $this */
		return $this->findRecursiveAttr(
			'service',
			'serviceRecursive',
			'parent'
		);
	}

	public function getScheduleRecursive()
	{
		/** @var MaintenanceJobs $this */
		return $this->findRecursiveAttr(
			'schedule',
			'scheduleRecursive',
			'parent'
		);
	}
	
	public function getReqsRecursive()
	{
		/** @var MaintenanceJobs $this */
		return $this->findRecursiveAttr(
			'reqs',
			'reqsRecursive',
			'parent'
		);
	}
	
	public function getDescriptionRecursive()
	{
		$description=$this->description;
		/** @var MaintenanceJobs $this */
		if (strpos($description,'{{PARENT}}')===false) return $description;
		$parent=is_object($this->parent)?$this->parent->description:'';
		return str_replace('{{PARENT}}', $parent, $description);
	}
	
	/**
	 * Удовлетворяет ли эта операция обслуживания требованию из аргумента
	 * @param MaintenanceReqs $req
	 * @return false
	 */
	public function satisfiesReq(MaintenanceReqs $req)
	{
		if (!is_array($this->reqs)) return false;	//если она не удовлетворяет ничему, то и искомому тоже не удовлетворяет
		foreach ($this->reqs as $test) {			//если это требование перечислено явно в этой операции то успех
			if ($req->id == $test->id) return true;
		}
		//явно не перечислено, тогда поищем может это требование удовлетворяется другими требованиями и они перечислены явно
		foreach ($req->satisfiedBy() as $parent) {
			if ($this->satisfiesReq($parent)) return true;
		}
		return false;
	}
	
}