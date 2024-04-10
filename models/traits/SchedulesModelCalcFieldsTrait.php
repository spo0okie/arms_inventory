<?php
/**
 * Вычисляемые поля для записей доступа (ACEs)
 */

namespace app\models\traits;





use app\helpers\ArrayHelper;
use app\helpers\DateTimeHelper;
use app\helpers\TimeIntervalsHelper;
use app\models\ArmsModel;
use app\models\Schedules;
use app\models\SchedulesEntries;
use app\models\Services;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;

/**
 * @package app\models\traits
 * @property ArmsModel[] $usedBy
 * @property boolean $isPrivate
 * @property Services[] $services
 * @property boolean isAcl
 * @property boolean isOverride
 * @property integer startUnixTime
 * @property integer endUnixTime
 * @property integer status
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
	 * Возвращает признак того, что расписание заканчивается до даты
	 * (т.к. у расписания может быть начало и конец действия)
	 * @param $date
	 * @return bool
	 */
	public function endsBeforeDate($date) {
		/** @var Schedules $this */
		if (!is_int($date))			//если передано не числом
			$date=strtotime($date); //конвертируем в Unixtime
		
		//есть конец и он раньше даты
		return ($this->endUnixTime && $this->endUnixTime<$date);
	}
	
	/**
	 * Возвращает признак того, что расписание начинается после даты
	 * (т.к. у расписания может быть начало и конец действия)
	 * @param $date
	 * @return bool
	 */
	public function startsAfterDate($date) {
		/** @var Schedules $this */
		if (!is_int($date))			//если передано не числом
			$date=strtotime($date); //конвертируем в Unixtime
		
		//начало есть и оно позже даты
		return ($this->startUnixTime && $this->startUnixTime>$date);
	}
	
	/**
	 * Возвращает признак того, что расписание перекрывает дату
	 * (т.к. у расписания может быть начало и конец действия)
	 * @param $date
	 * @return bool
	 */
	public function matchDate($date) {
		/** @var Schedules $this */
		if (!is_int($date))			//если передано не числом
			$date=strtotime($date); //конвертируем в Unixtime
		//начало есть и оно позже даты
		if ($this->startsAfterDate($date)) return false;
		
		//есть конец и он раньше даты
		if ($this->endsBeforeDate($date)) return false;
		
		return true;
	}
	
	
	/**
	 * Находит расписание недели, которое действует на дату
	 * @param $date
	 * @return Schedules|null
	 */
	public function getWeekSchedule($date) {
		/** @var Schedules $this */
		//перебираем перекрытия расписания в поисках перекрытия даты
		foreach ($this->overrides as $override)
			if ($override->matchDate($date)) return $override;
		
		//перекрывает ли расписание в целом эту дату
		if ($this->matchDate($date)) return $this;
		
		//никуда не попали. Дата за пределами расписания
		return null;
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
	 * Вытаскивает слово из словаря с учетом использования расписания (выше)
	 * @param $word
	 * @return string
	 */
	public function getDictionary($word) {
		/** @var Schedules $this */
		if (!isset(Schedules::$dictionary[$word]))
			return $word;

		return Schedules::$dictionary[$word][$this->getProvidingMode()]??Schedules::$dictionary[$word];
	}
	
	/**
	 * Ищет запись про какой-то день/дату (не период)
	 * @param $day
	 * @return mixed|null
	 */
	public function getDayEntry($day) {
		/** @var Schedules $this */
		if (!isset($this->attrsCache['daysEntries'])) {
			$this->attrsCache['daysEntries']=['def'=>null,'1'=>null,'2'=>null,'3'=>null,'4'=>null,'5'=>null,'6'=>null,'7'=>null];
			foreach ($this->entries as $entry)
				if (!$entry->is_period)
					$this->attrsCache['daysEntries'][$entry->date]=$entry;
		}
		return $this->attrsCache['daysEntries'][$day]??null;
	}
	
	/**
	 * все привязанные периоды к расписанию
	 * @return array
	 */
	public function getPeriods() {
		/** @var Schedules $this */
		if (!isset($this->attrsCache['periods'])) {
			$this->attrsCache['periods']=[];
			foreach ($this->entries as $entry)
				if ($entry->is_period)
					$this->attrsCache['periods'][]=$entry;
		}
		return $this->attrsCache['periods'];
	}
	
	/**
	 * Ищет график работы на конкретный день учитывая родительские расписания и перекрытия
	 * @param $day string день недели
	 * @param $date string|null если указать дату, то будет подбирать точно на дату с учетом перекрыавющих расписаний
	 * 							(если не указать, то работает с этим расписанием)
	 * @return SchedulesEntries|null
	 */
	public function getDayEntryRecursive($day, $date)
	{
		/** @var Schedules $this */
		
		//перекрывающие периоды данные ниоткуда не наследуют
		if ($this->isOverride) return $this->getDayEntry($day);
		
		//ищем расписание действующей на эту дату (если даты нет, то просто текущее расписание)
		$period=is_null($date)?$this:$this->getWeekSchedule($date);
		
		
		if (!is_null($period) && !is_null($daySchedule=$period->getDayEntry($day))) {
			return $daySchedule;
		}
		
		if (is_object($this->parent)) {
			return $this->parent->getDayEntryRecursive($day,$date);
		} else {
			return null;
		}
	}
	
	/**
	 * Ищет график работы на конкретный день недели
	 * - учитывая родительские расписания
	 * - учитывая перекрытия (если указана $date, то ищет перекрытие расписания на этот день)
	 * @param $weekday
	 * @param $date
	 * @return SchedulesEntries|null
	 */
	public function getWeekdayEntryRecursive($weekday, $date)
	{
		/** @var Schedules $this */
		
		//ищем расписание на этот день недели
		if (is_null($daySchedule=$this->getDayEntryRecursive($weekday,$date))) {
			//если не получилось - ищем расписание на каждый день
			if (is_null($daySchedule=$this->getDayEntryRecursive('def',$date))) {
				return null;
			};
		}
		$daySchedule=clone $daySchedule;
		//мы же находим не совсем то что искали. вписываем что мы вообще искали
		//чтобы можно было посмотреть предыдущий день
		$daySchedule->requestedWeekDay=$weekday;
		$daySchedule->requestedDate=$date;
		return $daySchedule;
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
	
	
	
	
	/**
	 * Ищет график работы на конкретную дату в *исходном* виде учитывая
	 * - границы действия расписания
	 * - родительские расписания
	 * - расписания-перекрытия
	 * - даты-исключения
	 * но не учитывая периоды включения/выключения
	 * @param $date
	 * @return SchedulesEntries|null
	 */
	public function getDateEntryRecursive($date)
	{
		
		//ищем расписание-исключение на этот день недели
		if (!is_null($daySchedule=$this->getDayEntryRecursive($date,null))) return $daySchedule;
		
		//если не получилось - ищем расписание на эту дату по дню недели
		//выясняем день недели на эту дату
		$words=explode('-',$date);
		if (count($words)<3) return null; //ошибка. передана дата не в формате ГГГГ-ММ-ДД
		//формат даты правильный
		
		//если у расписания есть границы и мы вне них то нет никакого графика на этот день
		$unixDate=strtotime($date);
		if (
			($this->startUnixTime && $unixDate<$this->startUnixTime)
			||
			($this->endUnixTime && $unixDate>$this->endUnixTime)
		) return null;
		
		//мы внутри границ расписания и у нас нормальная дата. Получаем день недели
		$weekday=date('N',mktime(0,0,0,$words[1],$words[2],$words[0]));
		
		return $this->getWeekdayEntryRecursive($weekday,$date);
	}
	
	/**
	 * возвращает расписание на день в рабочем(00:00-06:00,22:00-23:59 против читабельного 22:00-06:00) виде исходя из
	 * - графика на день с учетом
	 *  - границы действия расписания
	 *  - родительские расписания
	 *  - расписания-перекрытия
	 *  - даты-исключения
	 * + перекрытия рабочими интервалами
	 * + нерабочими
	 * @param $date
	 * @return array|object
	 */
	public function getDateSchedule($date)
	{
		//источники сбора расписания
		$sources=[];
		
		//объект расписания на день
		$dateScheduleEntry=$this->getDateEntryRecursive($date);
		
		//если ничего не нашли - создаем пустой
		if (!is_object($dateScheduleEntry)) {
			$dateScheduleEntry=new SchedulesEntries();
			$dateScheduleEntry->load([
				'is_period'=>0,
				'schedule'=>'-',
				'date'=>'def'
			],'');
		} else $dateScheduleEntry=clone $dateScheduleEntry;
		
		//добавляем объект расписания на предыдущий день
		$dateScheduleEntry->previousDateEntry=$this->getDateEntryRecursive(DateTimeHelper::previousDay($date));
		
		//ищем периоды работы/отдыха перекрывающие этот день
		$periods=$this->findPeriods(
			strtotime($date.' 00:00:00'),
			strtotime($date.' 23:59:59')
		);
		
		if (is_object($dateScheduleEntry->master)) {
			$sources['master']=$dateScheduleEntry->master;
		}
		
		if (count($periods)) {
			$sources['periods']=$periods;
		}
		
		//если никакие периоды не накладываются то возвращаем обычный график
		//if (!count($periods)) return $dateScheduleEntry;
		
		//иначе правим исходный график периодами работы не работы
		//формируем интервалы рабочего времени
		
		//рабочие интервалы в формате unixtime формируем сначала из графика на день
		$positive=$dateScheduleEntry->getIntervals($date);
		//var_dump($positive);
		$posPeriods=[];
		
		//нерабочих изначально нет, их ищем в периодах
		$negative=[];
		$negPeriods=[];
		
		foreach ($periods as $period) {
			if (is_object($period)) {
				if ($period->is_work) {//если рабочий период, то кладем в рабочие интервалы
					$positive = array_merge($positive, $period->getIntervals($date));
					$posPeriods[]=$period;
				} else {
					$negative = array_merge($negative, $period->getIntervals($date));
					$negPeriods[]=$period;
				}
			}
		}
		$positive=TimeIntervalsHelper::intervalMerge($positive);
		
		if (count($negative)) {
			//есть нерабочие интервалы - они перекрывают рабочие
			//слепляем в кучку
			$negative=TimeIntervalsHelper::intervalMerge($negative);
			
			//вычитаем нерабочие из рабочих
			$positive=TimeIntervalsHelper::intervalsSubtraction($positive,$negative);
		}
		
		//сортируем интервалы
		TimeIntervalsHelper::intervalsSort($positive);
		
		//формируем обратно расписание на день
		$arSchedule=[];
		foreach ($positive as $interval)
			$arSchedule[]=SchedulesEntries::unixIntervalToSchedule($interval);
		$strSchedule=count ($arSchedule)?
			implode(',',$arSchedule)
			:
			'-';
		$dateScheduleEntry->schedule=$strSchedule;
		//возвращаем комплексный массив данных
		return [
			'schedule' => $strSchedule,
			'day' => $dateScheduleEntry,
			'posPeriods' => $posPeriods,
			'negPeriods' => $negPeriods,
			'sources'	 => $sources,
		];
	}
	
	/**
	 * Проверка что переданные дата/время попадают в интервал рабочего времени
	 * @param $date
	 * @param $time
	 * @return int
	 */
	public function isWorkTime($date,$time)
	{
		$scheduleArray=$this->getDateSchedule($date);
		if (!is_array($scheduleArray) || !isset($scheduleArray['day'])) return 0;
		$schedule=$scheduleArray['day'];
		if (!is_object($schedule)) return 0;
		$periods=$schedule->schedulePeriods;
		$now= SchedulesEntries::strTimestampToMinutes($time);
		foreach ($periods as $period) {
			$interval= SchedulesEntries::scheduleExToMinuteInterval($period);
			if (TimeIntervalsHelper::intervalCheck($interval,$now)) return	1;
		}
		return 0;
	}
	
	public function getStatus(){
		return $this->isWorkTime(
			gmdate('Y-m-d',time()+ Yii::$app->params['schedulesTZShift']),
			gmdate('H:i',time()+ Yii::$app->params['schedulesTZShift'])
		);
	}
	
	/**
	 * Возвращает метаданные из расписания на время/дату
	 * @param $date
	 * @param $time
	 * @return mixed|string
	 */
	public function metaAtTime($date,$time)
	{
		//объект расписания на дату
		$dateScheduleEntry=$this->getDateEntryRecursive($date);
		//объект расписания на предыдущий день
		$dateScheduleEntry->previousDateEntry=$this->getDateEntryRecursive(DateTimeHelper::previousDay($date));

		$now= SchedulesEntries::strTimestampToMinutes($time);
		foreach ($dateScheduleEntry->getWorkMinuteIntervalsEx() as $interval) {
			if (TimeIntervalsHelper::intervalCheck($interval,$now)) {
				if ($interval['meta']===false) return '{}';
				else return $interval['meta'];
			}
		}
		return '{}';
	}
	
	/**
	 * Возвращает метаданные из расписания на время/дату
	 * либо ищет ближайшие следующие метаданные на 7 дней вперед
	 * @param $date
	 * @param $time
	 * @return mixed|string
	 */
	public function nextWorkingMeta($date,$time)
	{
		//если прямо сейчас уже рабочий период, то возвращаем его метаданные
		if (($meta=$this->metaAtTime($date,$time))!=='{}') return $meta;
		
		//что мы собственно делаем дальше
		//перебираем 8 дней начиная с текущего в поисках дня, в котором есть рабочие периоды
		//почему 8, потому что если в неделе есть всего один рабочий период, и он сегодня уже прошел,
		//то он повторится через 7 дней на 8й
		//TODO: с учетом периодов - нихера не так.
		// Возможно что после 8 дней надо перебирать еще по 7 дней в каждом предстоящем периоде
		$testDate=strtotime(date($date.' 00:00:00+0000'));
		$testTimestamp=strtotime(date($date.' '.$time.':00+0000'));
		for ($i=0;$i<=7;$i++) {
			$day=gmdate('Y-m-d',$testDate+86400*$i);
			//объект расписания на дату
			$dateScheduleEntry=$this->getDateEntryRecursive($day);
			//объект расписания на предыдущий день
			$dateScheduleEntry->previousDateEntry=$this->getDateEntryRecursive(DateTimeHelper::previousDay($day));
			if (count($intervals=$dateScheduleEntry->getWorkMinuteIntervalsEx())) {
				foreach ($intervals as $interval) {
					if ($interval[0]*60+$testDate+86400*$i >= $testTimestamp) {
						if ($interval['meta']!==false) return $interval['meta'];
					}
				}
			}
		}
		
		//дошли до сюда, значит в расписании на неделю ничего не нашли
		//ищем только по исключениям теперь
		/*$now=\app\models\SchedulesEntries::strTimestampToMinutes($time);
		foreach ($periods as $period) {
			$interval=\app\models\SchedulesEntries::scheduleExToMinuteInterval($period);
			if (self::intervalCheck($interval,$now)) {
				if ($interval['meta']===false) return '{}';
				else return $interval['meta'];
			};
		}*/
		return '{}';
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
}