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
 * @property MaintenanceReqs[] $backupReqs
 * @property MaintenanceReqs[] $unsatisfiedMaintenanceReqs
 * @property MaintenanceReqs[] $unsatisfiedBackupReqs
 * @property int $backupReqsCount
 * @property int $unsatisfiedBackupReqsCount
 */

trait UnsatisfiedMaintenanceFieldTrait
{
	
	/**
	 * Требования, которые не удовлетворяются ни одной из операций обслуживания
	 * @return MaintenanceReqs[]
	 */
	public function getUnsatisfiedMaintenanceReqs()
	{
		/** @var Comps $this */
		if (!isset($this->attrsCache['unsatisfiedMaintenanceReqs'])) {
			$this->attrsCache['unsatisfiedMaintenanceReqs']=[];

			foreach ($this->effectiveMaintenanceReqs as $req) {
				if (!$req->isSatisfiedByJobs($this->maintenanceJobs)) $this->attrsCache['unsatisfiedMaintenanceReqs'][]=$req;
			}
			
		}
		return $this->attrsCache['unsatisfiedMaintenanceReqs'];
	}
	
	/**
	 * Требования по резервному копированию на узле
	 * @return MaintenanceReqs[]
	 */
	public function getBackupReqs()
	{
		/** @var Comps $this */
		if (!isset($this->attrsCache['backupReqs'])) {
			$this->attrsCache['backupReqs']=[];
			
			foreach ($this->effectiveMaintenanceReqs as $req) {
				if ($req->is_backup) $this->attrsCache['backupReqs'][]=$req;
			}
			
		}
		return $this->attrsCache['backupReqs'];
	}
	
	/**
	 * Неудовлетворенные требования по резервному копированию на узле
	 * @return MaintenanceReqs[]
	 */
	public function getUnsatisfiedBackupReqs()
	{
		/** @var Comps $this */
		if (!isset($this->attrsCache['unsatisfiedBackupReqs'])) {
			$this->attrsCache['unsatisfiedBackupReqs']=[];
			
			foreach ($this->unsatisfiedMaintenanceReqs as $req) {
				if ($req->is_backup) $this->attrsCache['unsatisfiedBackupReqs'][]=$req;
			}
			
		}
		return $this->attrsCache['unsatisfiedBackupReqs'];
	}

	/**
	 * Количество эффективных требований предъявляемых к узлу
	 * @return int
	 */
	public function getBackupReqsCount() {
		return count ($this->backupReqs);
	}
	
	/**
	 * Количество не удовлетворенных требований предъявляемых к узлу
	 * @return int
	 */
	public function getUnsatisfiedBackupReqsCount() {
		return count ($this->unsatisfiedBackupReqs);
	}
	
}