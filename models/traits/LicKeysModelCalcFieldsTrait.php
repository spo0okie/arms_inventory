<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;



trait LicKeysModelCalcFieldsTrait
{
	public function getServiceRecursive()
	{
		return $this?->licItem?->serviceRecursive??null;
	}
	public function getResponsible()
	{
		return $this?->serviceRecursive?->responsible??null;
	}
	
	public function getSupport()
	{
		return $this?->serviceRecursive?->support??null;
	}
	
	/**
	 *
	 */
	public function getKeyShort(){
		if (strlen($this->key_text)<=10) return $this->key_text;
		return substr($this->key_text,0,5).' ... '.substr($this->key_text,-5,5);
	}
	
	/**
	 * Search name
	 * @return string
	 */
	public function getSname()
	{
		return $this->licItem->licGroup->descr.' /'.$this->licItem->fullDescr.' /'.$this->keyShort;
	}
	
	/**
	 * Display name
	 * @return string
	 */
	public function getDname()
	{
		return $this->licItem->licGroup->descr.' /'.$this->licItem->descr.' /'.$this->keyShort;
	}
	
	public function getName()
	{
		return $this->keyShort;
	}
	
	public function getSoftIds()
	{
		return $this->licItem->softIds;
	}
}