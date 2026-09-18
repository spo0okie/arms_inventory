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

	/**
	 * Описывает ли запись проброс соединения. Признак — на самой записи (хопе), а не на
	 * типе доступа: проброс ортогонален протоколу, типы остаются обычными (HTTPS, RDP…).
	 * @return bool
	 */
	public function hasForwardAccess(){
		/** @var Aces $this */
		return (bool)$this->is_forward;
	}

	/**
	 * Правила проброса этой записи: по одному на каждый сетевой (IP) тип доступа.
	 * Параметры берутся из ip_params записи (фолбэк — параметры типа по умолчанию)
	 * и раскладываются на вход/назначение: «TCP 443->8443». Запись-проброс без
	 * сетевых типов даёт одно правило без параметров — проброс всё равно должен быть виден.
	 * @return array [['type'=>AccessTypes|null, 'params'=>string, 'ext'=>string, 'int'=>string], ...]
	 */
	public function getForwardRules(): array {
		/** @var Aces $this */
		if (isset($this->attrsCache['forwardRules'])) return $this->attrsCache['forwardRules'];
		if (!$this->hasForwardAccess()) return $this->attrsCache['forwardRules']=[];
		$rules=[];
		if (is_array($this->accessTypes)) {
			$ipParams=$this->hasMethod('getIpParams')?$this->getIpParams():[];
			foreach ($this->accessTypes as $accessType) {
				if (!is_object($accessType) || !$accessType->is_ip) continue;
				$params=trim((string)($ipParams[$accessType->id]??''));
				if ($params==='') $params=trim((string)$accessType->ip_params_def);
				[$ext,$int]=Aces::parseForwardParams($params);
				$rules[]=['type'=>$accessType,'params'=>$params,'ext'=>$ext,'int'=>$int];
			}
		}
		if (!count($rules)) $rules[]=['type'=>null,'params'=>'','ext'=>'','int'=>''];
		return $this->attrsCache['forwardRules']=$rules;
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
		foreach ($this->segments as $subject)
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
		//сегмент разворачивается в свои подсети и узлы своих сервисов
		foreach ($this->segments as $segment)
			$this->attrsCache['nodes']=ArrayHelper::recursiveOverride(
				$this->attrsCache['nodes'],
				$segment->accessNodes
			);
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