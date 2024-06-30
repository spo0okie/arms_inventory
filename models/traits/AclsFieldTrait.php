<?php
/**
 * Вычисляемые поля для Объектов, у которых есть поле Acls
 */

namespace app\models\traits;




use app\models\Aces;
use app\models\Comps;

/**
 * @package app\models\traits

 * @property Aces $incomingAcls
 */

trait AclsFieldTrait
{
	
	/**
	 * Все ACE нацеленные на этот сервис
	 * @return Aces[]
	 */
	public function getIncomingAces() {
		/** @var Comps $this */
		if (isset($this->attrsCache['incomingAces']))
			return $this->attrsCache['incomingAces'];
		
		$this->attrsCache['incomingAces']=[];
		$acls=$this->acls;
		foreach ($acls as $acl) {
			if (is_object($acl)) {
				foreach ($acl->aces as $ace) {
					$this->attrsCache['incomingAces'][$ace->id]=$ace;
				}
			}
		}
		return $this->attrsCache['incomingAces'];
	}
	
}