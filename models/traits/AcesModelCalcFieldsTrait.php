<?php
/**
 * Вычисляемые поля для записей доступа (ACEs)
 */

namespace app\models\traits;





use app\models\Aces;

/**
 * @package app\models\traits
 */

trait AcesModelCalcFieldsTrait
{
	static $NAME_MISSING='Пояснение отсутствует';
	/**
	 * Типы доступа (ничего не понял, нельзя же 2 раза выбрать один тип доступа для одного ACE)
	 */
	public function getAccessTypesUniq()
	{
		/** @var Aces $this */
		if (!is_array($this->accessTypes)) return[];
		$types=[];
		foreach ($this->accessTypes as $type)
			$types[$type->id]=$type;
		return $types;
	}
	
	/**
	 * Набор пользователей (и два раза пользователя нельзя выбрать. как так?? зачем это?)
	 */
	public function getUsersUniq()
	{
		/** @var Aces $this */
		if (!is_array($this->users)) return[];
		$users=[];
		foreach ($this->users as $user)
			$users[$user->id]=$user;
		return $users;
	}
	
	/**
	 * Набор пользователей
	 */
	public function getDepartments()
	{
		/** @var Aces $this */
		if (!is_array($this->usersUniq)) return[];
		$departments=[];
		foreach ($this->usersUniq as $user)
			if (is_object($department=$user->orgStruct))
				$departments[$department->id]=$department;
		return $departments;
	}
	
	public function getPartners() {
		/** @var Aces $this */
		if (!count($this->users_ids)) return [];
		$partners=[];
		foreach ($this->users as $user)
			$partners[$user->org_id]=$user->org;
		return $partners;
	}
	
	
	public function hasIpAccess(){
		/** @var Aces $this */
		foreach ($this->accessTypesUniq as $accessType) {
			if ($accessType->isIpRecursive) return true;
		}
		return false;
	}
	
	public function hasPhoneAccess(){
		/** @var Aces $this */
		foreach ($this->accessTypesUniq as $accessType) {
			if ($accessType->isTelephonyRecursive) return true;
		}
		return false;
	}

	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		if ($this->name) return $this->name;
		return static::$NAME_MISSING;
	}
	
	
}