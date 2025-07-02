<?php
/**
 * Вычисляемые поля для Объектов, у которых есть поле Acls
 */

namespace app\models\traits;




use app\models\Aces;
use app\models\Comps;
use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;

/**
 * @package app\models\traits
 * @property MaintenanceJobs[] $maintenanceJobs
 * @property MaintenanceReqs[] $effectiveMaintenanceReqs
 * @property int $effectiveMaintenanceReqsCount
 * @property MaintenanceReqs[] $unsatisfiedMaintenanceReqs
 * @property int $unsatisfiedMaintenanceReqsCount
 */

trait UnsatisfiedMaintenanceFieldTrait
{
	
	/**
	 * Требования, которые не удовлетворяются ни одной из операций обслуживания
	 * @return MaintenanceReqs[]
	 */
	public function getUnsatisfiedMaintenanceReqs()
	{
		$reqs=[];
		foreach ($this->effectiveMaintenanceReqs as $req) {
			if (!$req->isSatisfiedByJobs($this->maintenanceJobs)) $reqs[]=$req;
		}
		return $reqs;
	}
	
	/**
	 * Количество эффективных требований предъявляемых к узлу
	 * @return int
	 */
	public function getEffectiveMaintenanceReqsCount() {
		return count ($this->effectiveMaintenanceReqs);
	}
	
	/**
	 * Количество не удовлетворенных требований предъявляемых к узлу
	 * @return int
	 */
	public function getUnsatisfiedMaintenanceReqsCount() {
		return count ($this->unsatisfiedMaintenanceReqs);
	}
	
}