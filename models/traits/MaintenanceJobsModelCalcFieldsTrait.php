<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;




use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Scans;
use app\models\Users;
use yii\db\ActiveQuery;

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
	
	/*public function getServiceRecursive()
	{
		/** @var MaintenanceJobs $this
		return $this->findRecursiveAttr(
			'service',
			'serviceRecursive',
			'parent'
		);
	}

	public function getScheduleRecursive()
	{
		/** @var MaintenanceJobs $this
		return $this->findRecursiveAttr(
			'schedule',
			'scheduleRecursive',
			'parent'
		);
	}
	
	public function getReqsRecursive()
	{
		/** @var MaintenanceJobs $this
		return $this->findRecursiveAttr(
			'reqs',
			'reqsRecursive',
			'parent'
		);
	}
	
	/*public function getDescriptionRecursive()
	{
		return $this->textRecursiveField('description','descriptionRecursive');
	}*/
	
	/**
	 * Удовлетворяет ли эта операция обслуживания требованию из аргумента
	 * @param MaintenanceReqs $req
	 * @return bool
	 */
	public function satisfiesReq(MaintenanceReqs $req)
	{
		//если она не удовлетворяет ничему, то и искомому тоже не удовлетворяет
		if (!is_array($this->reqs)) return false;
		
		//если это требование перечислено явно в этой операции, то успех
		foreach ($this->reqsRecursive as $test) {
			if ($req->id == $test->id) return true;
		}
		
		//явно не перечислено, тогда проверяем что
		//это требование удовлетворяется другими требованиями, и они перечислены явно
		foreach ($req->satisfiedBy() as $parent) {
			if ($this->satisfiesReq($parent)) return true;
		}
		return false;
	}
	
	/**
	 * Все потомки (включая потомков от потомков)
	 * @return MaintenanceJobs[]|ActiveQuery
	 */
	public function getChildrenRecursive()
	{
		$items=$this->children??[];
		$result=$items;
		foreach ($items as $item) {
			$result=array_merge($result,$item->getChildrenRecursive());
		}
		return $result;
	}
	
}