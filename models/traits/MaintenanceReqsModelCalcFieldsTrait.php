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

trait MaintenanceReqsModelCalcFieldsTrait
{
	/**
	 * Какими требованиями удовлетворяется
	 * слово included не особо поясняет что это значит, а satisfied вполне
	 * @return MaintenanceReqs[]
	 */
	public function satisfiedBy()
	{
		//TODO: Обработать состояние AllItemsLoaded, которое должно включать подгрузку не только самой таблицы,
		// но и таблиц many-2-many ссылок
		$included=$this->includedBy;
		return is_array($included)?$included:[];
	}
	
	/**
	 * Удовлетворяется ли требование другим требованием
	 * @param $req
	 * @return bool
	 */
	public function isSatisfiedByReq($req) {
		//нужно проверить что $req входит в массив непосредственно удовлетворяемых требований
		//либо удовлетворяется ими
		foreach ($this->satisfiedBy() as $item) {
			if ($item->id == $req->id) return true;		//если удовлетворяет непосредственно
			if ($item->isSatisfiedByReq($req)) return true;	//или рекурсивно
		}
		//TODO: Обработать состояние AllItemsLoaded, которое должно включать подгрузку не только самой таблицы,
		// но и таблиц many-2-many ссылок
		return false;
	}
	
	/**
	 * Удовлетворяется ли требование регламентным обслуживанием
	 * @param MaintenanceJobs $job
	 * @return bool
	 */
	public function isSatisfiedByJob($job) {
		return $job->satisfiesReq($this);
	}
	
	/**
	 * Удовлетворяется ли требование регламентными обслуживаниями
	 * @param MaintenanceJobs[] $jobs
	 * @return bool
	 */
	public function isSatisfiedByJobs($jobs) {
		foreach ($jobs as $job) {
			if ($this->isSatisfiedByJob($job)) return true;
		}
		return false;
	}
	
	/**
	 * Убирает из набора требований такие, которые удовлетворяются другими из набора
	 * @param MaintenanceReqs[] $reqs
	 * @return MaintenanceReqs[]
	 */
	public static function filterEffective(array $reqs)
	{
		$effective=[];
		//проверяем всех
		foreach ($reqs as $req) {
			if ($req->archived) continue;	//пропускаем архивные
			//ибо иначе прописанное свойство absorbed сохраняется в объекте, который один на множество компов
			$req1=clone $req;
			//со всеми
			foreach ($reqs as $req2) {
				if ($req2->archived) continue;	//пропускаем архивные
				//если элемент входит в набор удовлетворяемых требований - помечаем его
				if ($req1->isSatisfiedByReq($req2)) {
					$req1->absorbed=$req2->id;
					break;
				}
			}
			$effective[]=$req1;
		}
		return $effective;
	}
	
	public function getArchivedOrAbsorbed() {
		return $this->absorbed || $this->archived;
	}

}