<?php
/**
 * Вычисляемые поля для записей доступа (ACEs)
 */

namespace app\modules\schedules\models\traits;




use app\helpers\ArrayHelper;
use app\helpers\DateTimeHelper;
use app\models\Acls;
use app\models\base\ArmsModel;
use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use app\models\Services;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;

/**
 * @package app\modules\schedules\models\traits
 * @property Acls[]                       $acls
 * @property \app\models\base\ArmsModel[] $usedBy
 * @property boolean                      $isPrivate
 * @property Services[]                   $services
 * @property boolean                      isAcl
 * @property boolean                      isOverride
 * @property integer                      startUnixTime
 * @property integer                      endUnixTime
 * @property integer                      status
 */

trait SchedulesModelCalcFieldsTrait
{
	public $metaClasses=[]; //список классов метаданных привязанных к этому расписанию
	public $defaultItemSchedule;
	
	public function getIsAcl() {
		/** @var Schedules $this */
		if (!isset($this->attrsCache['isAcl'])) {
			$this->attrsCache['isAcl']=is_array($this->acls) && count($this->acls);
		}
		return $this->attrsCache['isAcl'];
	}
	
	public function getAces() {
		if (isset($this->attrsCache['aces']))
			return $this->attrsCache['aces'];
		
		$this->attrsCache['aces']=[];
		foreach ($this->acls as $acl)
			foreach ($acl->aces as $ace)
				$this->attrsCache['aces'][]=$ace;
			
		return $this->attrsCache['aces'];
	}
	
	public function getIsOverride() {
		/** @var Schedules $this */
		return !is_null($this->override_id);
	}
	
	public function getBaseId() {
		/** @var Schedules $this */
		return $this->isOverride?$this->override_id:$this->id;
	}
	
	public function getBase() {
		/** @var Schedules $this */
		return $this->isOverride?$this->overriding:$this;
	}
	
	/**
	 * начало расписания (NULL если начала нет)
	 * с кэшированием
	 * @return int|null
	 */
	public function getStartUnixTime() {
		/** @var Schedules $this */
		if (isset($this->attrsCache['startUnixTime']))	return $this->attrsCache['startUnixTime'];
		
		if (is_null($this->start_date)) {
			$this->attrsCache['startUnixTime']=null;
		} else {
			$this->attrsCache['startUnixTime']=strtotime($this->start_date);
		}
		return $this->attrsCache['startUnixTime'];
	}
	
	/**
	 * конец расписания (NULL если конца нет)
	 * с кэшированием
	 * @return int|null
	 */
	public function getEndUnixTime() {
		/** @var Schedules $this */
		if (isset($this->attrsCache['endUnixTime']))	return $this->attrsCache['endUnixTime'];
		
		if (is_null($this->end_date)) {
			$this->attrsCache['endUnixTime']=null;
		} else {
			$this->attrsCache['endUnixTime']=strtotime($this->end_date);
		}
		return $this->attrsCache['endUnixTime'];
	}
	
	/**
	 * Возвращает кодовое слово режима использования расписания
	 * @return string
	 */
	public function getProvidingMode() {
		/** @var Schedules $this */
		if (isset($this->attrsCache['providingMode'])) return $this->attrsCache['providingMode'];
		
		if ($this->isOverride) $this->attrsCache['providingMode']=$this->overriding->providingMode;
		elseif ($this->isAcl) $this->attrsCache['providingMode']='acl';	//доступ предоставляется
		elseif (count($this->providingServices)) $this->attrsCache['providingMode']='providing'; //услуга предоставляется
		elseif (count($this->supportServices)) $this->attrsCache['providingMode']='support'; //услуга поддерживается
		elseif (count($this->maintenanceJobs)) $this->attrsCache['providingMode']='job'; //услуга поддерживается
		else $this->attrsCache['providingMode']='working'; //рабочее время
		return $this->attrsCache['providingMode'];
	}
	
	/**
	 * Вернуть цепочку от себя до последнего родителя
	 * @param array $chain
	 * @return array
	 */
	public function getParentsChain($chain=[]){
		/** @var Schedules $this */
		//если мы уже есть в цепи, значит цепь замкнулась и дальше искать смысла нет.
		if (isset($chain[$this->id])) return $chain;
		//добавляем себя в цепочку
		$chain[$this->id]=$this;
		//если у нас есть родитель
		return $this->parent_id?
			$this->parent->getParentsChain($chain):  //- передаем ему эстафету
			$chain; //возвращаем текущую цепочку
	}
	public function getAcePartners() {
		if (!is_array($this->acls)) return [];
		$partners=[];
		foreach ($this->acls as $acl) {
			$partners=ArrayHelper::recursiveOverride($partners,$acl->partners);
		}
		return $partners;
	}
	
	public function getAclSegments() {
		if (!is_array($this->acls)) return [];
		$segments=[];
		foreach ($this->acls as $acl) {
			$segments=ArrayHelper::recursiveOverride(
			//$segments=array_merge_recursive(
				$segments,
				ArrayHelper::index($acl->segments,'id')
			);
		}
		return $segments;
	}
	
	public function getAclSites() {
		if (!is_array($this->acls)) return [];
		$sites=[];
		foreach ($this->acls as $acl) {
			$sites= ArrayHelper::recursiveOverride(
				$sites,
				ArrayHelper::index($acl->sites,'id')
			);
		}
		return $sites;
	}
	
	public function getAceDepartments() {
		if (!is_array($this->acls)) return [];
		$departments=[];
		foreach ($this->acls as $acl) {
			$departments= ArrayHelper::recursiveOverride(
				$departments,
				$acl->departments
			);
		}
		return $departments;
	}
	
	public function getAccessTypes() {
		if (!is_array($this->acls)) return [];
		$types=[];
		foreach ($this->acls as $acl) {
			$types= ArrayHelper::recursiveOverride($types,$acl->accessTypes);
		}
		return $types;
	}
	
	/**
	 * Возвращает массив сервисов связанных с расписанием с указанием типа связи
	 * @return array
	 */
	public function getServicesArr()
	{
		$support=$this->supportServices;
		$provide=$this->providingServices;
		
		if (count($provide) || count($support)) {
			$services = [];
			foreach ($provide as $service) {
				$services[$service->id]['obj'] = $service;
				$services[$service->id]['provide'] = true;
			}
			foreach ($support as $service) {
				$services[$service->id]['obj'] = $service;
				$services[$service->id]['support'] = true;
			}
			return $services;
		} else {
			return [];
		}
	}

	/**
	 * Возвращает массив сервисов связанных с расписанием
	 * @return array
	 */
	public function getServices()
	{
		if (isset($this->attrsCache['services'])) return $this->attrsCache['services'];
		$support=$this->supportServices;
		$provide=$this->providingServices;
		
		$this->attrsCache['services'] = [];
		foreach ($provide as $service) {
			$this->attrsCache['services'][$service->id] = $service;
		}

		foreach ($support as $service) {
			$this->attrsCache['services'][$service->id] = $service;
		}
		
		return $this->attrsCache['services'];
	}
	
	public function getPeriodDescription()
	{
		return Schedules::generatePeriodDescription([$this->startUnixTime,$this->endUnixTime]);
	}
	
	
	/**
	 * Расписание работы на неделю в *читаемом* виде
	 * (с переходом на след день типа 22:00-06:00)
	 * - с учетом родительских расписаний
	 * - с учетом перекрытий
	 * @param null|integer $date на какую дату (по умолчанию на текущую)
	 * @return array
	 */
	public function getWeekWorkTime($date=null)
	{
		$date=DateTimeHelper::weekMonday($date);
		$days=['-','пн','вт','ср','чт','пт','сб','вс'];
		
		$description=[];		//итоговое описание расписания на неделю
		$previousSchedule='-';	//расписание на предыдущий день
		$previousDay=null;		//предыдущий день
		$periodFirstDay=null;	//первый день периода с одинаковым расписанием
		
		for ($i=1;$i<=8;$i++) {
			//описание строится по предыдущим дням (сравниваем расписание с предыдущим),
			// поэтому вылазим за воскресенье, чтобы закрыть описание воскресенья
			$scheduleObj=($i===8)?
				null
				:
				$this->getWeekdayEntryRecursive($i,$date+86400*($i-1));	//вытаскиваем расписание
			
			$schedule= is_object($scheduleObj)?
				$scheduleObj->getMergedSchedule()	//склеиваем его рабочие периоды
				:
				'-';
			
			
			//если расписание на этот день отличается от расписания на предыдущий
			if ($schedule!==$previousSchedule) {
				//$description[]="$schedule!==$previousSchedule ($periodFirstDay-$previousDay)";
				//если у нас есть и начало и конец предыдущего периода, и там есть рабочее время
				if (!is_null($previousDay) && !is_null($periodFirstDay) && $previousSchedule!=='-') {
					//то добавляем его в описание
					
					if ($periodFirstDay===$previousDay) {
						$description[]="{$days[$periodFirstDay]}: $previousSchedule";	//пн: 8:00-17:00
					} elseif ($periodFirstDay==1 && $previousDay==7) {
						$description[]=$previousSchedule.' '.Schedules::$allDaysTitle;						//8:00-17:00 ежедневно
					} else {
						$description[]="{$days[$periodFirstDay]}-{$days[$previousDay]}: $previousSchedule";	//пн-чт: 8:00-17:00
					}
				}
				
				//начинаем новый период
				$periodFirstDay=$i;
				
			}
			
			//обновляем предыдущий день и его график для след итерации
			$previousDay=$i;
			$previousSchedule=$schedule;
		}
		return $description;
	}
	
	/**
	 * Пробуем вернуть dataProvider расписания на неделю
	 *  - с учетом родителя
	 *  - без учета перекрытий
	 * @return ArrayDataProvider
	 */
	public function getWeekDataProvider()
	{
		$models=[];
		foreach (SchedulesEntries::$days as $day=> $name)
			$models[$day]=$this->getWeekdayEntryRecursive($day,null);
		return new ArrayDataProvider(['allModels'=>$models]);
	}
	
	/**
	 * Описание расписания на неделю в читаемом виде (с переходами на след день: 22:00-06:00)
	 * @param null $date
	 * @return mixed|string
	 */
	public function getWeekWorkTimeDescription($date=null) {
		if (count($periods=$this->getWeekWorkTime($date))) {
			$description=implode(', ',$periods);
			if ($description=='00:00-23:59 '.Schedules::$allDaysTitle)
				return $this->getDictionary('always');
			return $description;
		} else
			return '';
	}
	
	/**
	 * Пояснение применения расписания
	 */
	public function getUsageDescription() {
		if ($this->start_date && strtotime('today')<strtotime($this->start_date)) {
			return $this->getDictionary('usage_will_be');
		}
		if ($this->end_date && strtotime('today')>strtotime($this->end_date)) {
			return $this->getDictionary('usage_complete');
		}
		return $this->getDictionary('usage');
	}
	
	/**
	 * Пояснение периода действия расписания
	 * @return string
	 * @throws InvalidConfigException
	 */
	public function getDateWorkTimeDescription() {
		$tokens=[];
		if ($this->start_date) $tokens[]="с ". Yii::$app->formatter->asDate($this->start_date);
		if ($this->end_date) $tokens[]="до ". Yii::$app->formatter->asDate($this->end_date);
		return count($tokens)?implode(' ',$tokens):'';
	}
	
	/**
	 * Пояснение расписания безотносительно использования: график и период
	 * @return string
	 */
	public function getWorkTimeDescription() {
		$tokens=[];
		$weekDescription=$this->weekWorkTimeDescription;
		$dateDescription=$this->dateWorkTimeDescription;
		if ($weekDescription) $tokens[]=$weekDescription;
		if ($dateDescription) $tokens[]=$dateDescription;
		if (count($tokens)) return implode(' ',$tokens);
		return $this->getDictionary('nodata');
	}
	
	/**
	 * Пояснение расписания полное: зачем, график, какой период действия
	 * @return string
	 */
	public function getUsageWorkTimeDescription() {
		$tokens=[];
		$weekDescription=$this->getWeekWorkTimeDescription();
		$dateDescription=$this->getDateWorkTimeDescription();
		if ($weekDescription) $tokens[]=$weekDescription;
		if ($dateDescription) $tokens[]=$dateDescription;
		if (count($tokens)) return $this->getUsageDescription().' '.implode(' ',$tokens);
		return $this->getDictionary('nodata');
	}
	
	
	
	
	public function getStatus(){
		return $this->isWorkTime(
			gmdate('Y-m-d',time()+ Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+ Yii::$app->params['schedulesTZShift'])
		);
	}

	public function getAclStatus()
	{

	}

	/**
	 * Массив всех объектов использующих это расписание
	 * @return ArmsModel[]
	 */
	public function getUsedBy() {
		/** @var Schedules $this */
		if (!isset($this->attrsCache['usedBy']))
			$this->attrsCache['usedBy']=array_merge($this->services,$this->acls,$this->maintenanceJobs);
			
		return $this->attrsCache['usedBy'];
	}
	
	/**
	 * @return bool
	 */
	public function getIsPrivate() {
		if (!isset($this->attrsCache['isPrivate'])) {
			$this->attrsCache['isPrivate']=(count($this->usedBy)==1);
		}
		
		return $this->attrsCache['isPrivate'];
	}
	
	
	/**
	 * Путь до папки views
	 * @return mixed|string
	 */
	public function getViewsPath() {
		return $this->isAcl?'scheduled-access':'schedules';
	}
}
