<?php
/**
 * Вычисляемые поля для записей доступа (ACEs)
 */

namespace app\models\traits;





use app\helpers\ArrayHelper;
use app\models\AccessTypes;
use app\models\Aces;
use app\models\Users;

/**
 * @package app\models\traits
 */

trait AcesModelCalcFieldsTrait
{
	static $NAME_MISSING='Пояснение отсутствует';
	
	/**
	 * Набор пользователей
	 */
	public function getDepartments()
	{
		/** @var Aces $this */
		if (!is_array($this->users)) return[];
		$departments=[];
		foreach ($this->users as $user)
			if (is_object($department=$user->orgStruct))
				$departments[$department->id]=$department;
		return $departments;
	}
	
	public function getPartners() {
		/** @var Aces $this */
		//проверка "есть ли вообще пользователи" через кэш количеств - не загружая пользователей
		if (!($this->loaderCount('users') ?? count($this->users_ids))) return [];
		$partners=[];
		foreach ($this->users as $user)
			$partners[$user->org_id]=$user->org;
		return $partners;
	}
	
	
	public function hasIpAccess(){
		/** @var Aces $this */
		foreach ($this->accessLinks as $row) {
			$accessType=AccessTypes::getLoadedItem($row['access_types_id'],true);
			if (is_object($accessType) && $accessType->isIpRecursive) return true;
		}
		return false;
	}

	public function hasPhoneAccess(){
		/** @var Aces $this */
		foreach ($this->accessLinks as $row) {
			$accessType=AccessTypes::getLoadedItem($row['access_types_id'],true);
			if (is_object($accessType) && $accessType->isTelephonyRecursive) return true;
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
	
	/**
	 * Возвращает все субъекты досутпа одним списком
	 */
	public function getSubjects() {
		/** @var Aces $this */
		if (isset($this->attrsCache['subjects']))
			return $this->attrsCache['subjects'];
		$this->attrsCache['subjects']=[];
		foreach ($this->users as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->comps as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->services as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->netIps as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		foreach ($this->networks as $subject)
			$this->attrsCache['subjects'][$subject->uuid()] = $subject;
		if ($this->comment)
			$this->attrsCache['subjects'][$this->comment] = $this->comment;
		return $this->attrsCache['subjects'];
	}
	
	/**
	 * Возвращает все узлы субъектов доступа (сервисы разворачиваются в ОС и оборудование)
	 */
	public function getNodes() {
		/** @var Aces $this */
		if (isset($this->attrsCache['nodes']))
			return $this->attrsCache['nodes'];
		$this->attrsCache['nodes']=[];
		foreach ($this->users as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->comps as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->netIps as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->networks as $subject)
			$this->attrsCache['nodes'][$subject->uuid()] = $subject;
		foreach ($this->services as $service)
			$this->attrsCache['nodes']=ArrayHelper::recursiveOverride(
				$this->attrsCache['nodes'],
				$service->nodesRecursive
			);
		return $this->attrsCache['nodes'];
		
	}
	
	/**
	 * Архивность записи доступа.
	 *
	 * Наследуется от списка доступа (архивный ресурс либо истекшее расписание -
	 * доступ к мертвому ресурсу мертв независимо от субъектов) и наступает своя,
	 * когда ВСЕ субъекты записи ушли в архив: пока жив хоть один субъект, доступ
	 * действует. Запись без объектных субъектов (только текстовое «Прочее»)
	 * архивной не считается - архивироваться нечему.
	 * SQL-двойник - {@see Aces::aliveSubjectsCondition()} (см. AcesSearch).
	 *
	 * @return bool
	 */
	public function getArchived() {
		/** @var Aces $this */
		if (isset($this->attrsCache['archived'])) return $this->attrsCache['archived'];

		if (is_object($this->acl) && $this->acl->archived)
			return $this->attrsCache['archived']=true;

		$subjects=$this->subjects;
		if (!count($subjects)) return $this->attrsCache['archived']=false;

		foreach ($subjects as $subject)
			if (!static::subjectIsArchived($subject))
				return $this->attrsCache['archived']=false;

		return $this->attrsCache['archived']=true;
	}

	/**
	 * Ушел ли субъект доступа в архив: у сотрудника архив - это увольнение,
	 * у остальных объектов - собственный или вычисляемый признак archived.
	 * Текстовый субъект («Прочее») объектом не является и в архив не уходит.
	 *
	 * @param mixed $subject элемент списка {@see getSubjects()}
	 * @return bool
	 */
	public static function subjectIsArchived($subject): bool
	{
		if (!is_object($subject)) return false;
		if ($subject instanceof Users) return (bool)$subject->Uvolen;
		return $subject->canBeArchived && (bool)$subject->archived;
	}
	
}