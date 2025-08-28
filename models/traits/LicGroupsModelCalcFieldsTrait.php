<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;



trait LicGroupsModelCalcFieldsTrait
{
	public function getResponsible()
	{
		return $this?->service?->responsible??null;
	}
	
	public function getSupport()
	{
		return $this?->service?->support??null;
	}
	
}