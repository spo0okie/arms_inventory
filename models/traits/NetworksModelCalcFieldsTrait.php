<?php
/**
 * Вычисляемые поля для требований по обсл
 */

namespace app\models\traits;



trait NetworksModelCalcFieldsTrait
{
	/**
	 * CSS код сегмента к которому относится VLAN
	 * @return string
	 */
	public function getSegmentCode()
	{
		if (is_object($segment=$this->segment)) return $segment->code;
		return '';
	}
	
}