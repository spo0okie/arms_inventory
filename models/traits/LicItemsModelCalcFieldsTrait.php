<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;



trait LicItemsModelCalcFieldsTrait
{
	public function getServiceRecursive()
	{
		return $this?->service??$this->licGroup?->service??null;
	}
	public function getResponsible()
	{
		return $this?->serviceRecursive?->responsible??null;
	}
	
	public function getSupport()
	{
		return $this?->serviceRecursive?->support??null;
	}
	
}