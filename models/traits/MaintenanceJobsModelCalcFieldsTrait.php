<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;




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
		if (is_object($this->service)) return $this->service->responsibleRecursive;
		return null;
	}
	
	public function getSupport()
	{
		if (is_object($this->service)) return $this->service->supportRecursive;
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