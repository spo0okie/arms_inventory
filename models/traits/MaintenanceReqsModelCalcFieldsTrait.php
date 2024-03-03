<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;




use app\helpers\ArrayHelper;
use app\models\MaintenanceReqs;
use app\models\Scans;
use app\models\Services;
use app\models\Techs;
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
	 * Убирает из набора требований такие, которые удовлетворяются другими из набора
	 * @param MaintenanceReqs[] $reqs
	 * @return MaintenanceReqs[]
	 */
	public static function filterEffective(array $reqs)
	{
		//проверяем всех
		foreach ($reqs as $req) {
			//со всеми
			foreach ($reqs as $test) {
				//если элемент входит в набор удовлетворяемых требований - помечаем его
				if ($req->isSatisfiedByReq($test)) {
					$req->absorbed=$test->id;
					break;
				}
			}
		}
		return $reqs;
	}
	
	public function getArchivedOrAbsorbed() {
		return $this->absorbed || $this->archived;
	}

}