<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;



trait LicItemsModelCalcFieldsTrait
{
	public function getServiceEffective()
	{
		return $this?->service??$this->licGroup?->service??null;
	}
	public function getResponsible()
	{
		return $this?->serviceEffective?->responsible??null;
	}

	public function getSupport()
	{
		return $this?->serviceEffective?->support??null;
	}
	
}