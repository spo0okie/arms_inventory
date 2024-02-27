<?php
/**
 * Вычисляемые поля для расходов материалов
 */

namespace app\models\traits;





use app\models\Currency;
use app\models\MaterialsUsages;

/**
 * @package app\models\traits
 */

trait MaterialUsagesModelCalcFieldsTrait
{
	public function getCost()
	{
		/** @var MaterialsUsages $this */
		return ($this->material->count)?$this->material->cost/$this->material->count*$this->count:null;
	}
	
	public function getCharge()
	{
		/** @var MaterialsUsages $this */
		return ($this->material->count)?$this->material->charge/$this->material->count*$this->count:null;
	}
	
	/**
	 * @return Currency
	 */
	public function getCurrency()
	{
		/** @var MaterialsUsages $this */
		return $this->material->currency;
	}
	
	/**
	 * @return string
	 */
	public function getTo()
	{
		/** @var MaterialsUsages $this */
		$tokens=[];
		if(!empty($this->arm)) $tokens[]=$this->arm->num;
		if(!empty($this->tech)) $tokens[]=$this->tech->num;
		if(strlen($this->comment)) $tokens[]=$this->comment;
		return	implode(' ',$tokens);
	}
	
	/**
	 * @return string
	 */
	public function getSname()
	{
		/** @var MaterialsUsages $this */
		return $this->material->sname.':'.
			$this->count.
			$this->material->type->units.
			' -> '.
			$this->to;
	}
	
	
}