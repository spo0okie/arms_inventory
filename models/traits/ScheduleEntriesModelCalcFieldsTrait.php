<?php
/**
 * Вычисляемые поля для записей расписания
 */

namespace app\models\traits;





use app\helpers\TimeIntervalsHelper;
use app\models\SchedulesEntries;

/**
 * @package app\models\traits
 */

trait ScheduleEntriesModelCalcFieldsTrait {
	/**
	 * @var string по запросу какого дня недели была найдена эта запись
	 * мы могли искать понедельник (1) а найти (def)
	 */
	public $requestedWeekDay;
	
	/**
	 * По запросу какой даты была найдена эта запись
	 * @var string
	 */
	public $requestedDate;
	
	
	/**
	 * Предыдущий день из которого нам надо взять кусок расписания переходящий на этот день
	 * если в предыдущий день было расписание 22:00-06:00, то нам для получения рабочего времени на этот день
	 * надо взять оттуда кусок 00:00-06:00 и добавить в это рабочее время
	 * @var SchedulesEntries
	 */
	public $previousDateEntry;
	
	public $isAclCache=null;
	
	public function getIsAcl() {
		if (is_null($this->isAclCache)) {
			$this->isAclCache=is_object($this->master) && $this->master->isAcl;
		}
		return $this->isAclCache;
	}
	
	public function getIsWorkDescription() {
		if ($this->isAcl)
			return SchedulesEntries::$isWorkComment['acl'][$this->is_work];
		return SchedulesEntries::$isWorkComment['default'][$this->is_work];
		
	}
	
	/**
	 * Возвращает описание даты записи
	 * @return string
	 */
	public function getDay() {
		if (isset(SchedulesEntries::$days[$this->date])) return SchedulesEntries::$days[$this->date];
		return $this->date;
	}
	
	/**
	 * Возвращает описание даты записи
	 * @return string
	 */
	public function getDayFor() {
		if (isset(SchedulesEntries::$daysFor[$this->date])) return SchedulesEntries::$daysFor[$this->date];
		return $this->date;
	}
	
	public function getScheduleWithoutMetadata() {
		/** @var SchedulesEntries $this */
		return SchedulesEntries::scheduleWithoutMetadata($this->schedule);
	}

	public function getMergedSchedule() {
		/** @var SchedulesEntries $this */
		if ($this->schedule === '-') return '-';
		//сначала приводим интервалы к математически корректным
		$intervals=TimeIntervalsHelper::dayMinutesOverheadFixAll($this->getMinuteIntervals());
		$intervals=TimeIntervalsHelper::intervalMerge($intervals);
		$intervals=TimeIntervalsHelper::dayMinutesOverheadHumanizeAll($intervals); //возвращаем привычный человеку (математически некорректный) вид
		
		//var_dump($intervals);
		$timestamps=[];
		foreach ($intervals as $interval)
			$timestamps[]=SchedulesEntries::minuteIntervalToSchedule($interval);
		return implode(',',$timestamps);
	}
	
	public function getSchedulePeriods() {
		if ($this->schedule==='-') return [];
		return explode(',',$this->schedule);
	}
	
	public function getMinuteIntervals() {
		if ($this->schedule==='-') return [];
		$tokens=explode(',',$this->scheduleWithoutMetadata);
		$intervals=[];
		foreach ($tokens as $token) {
			$interval=SchedulesEntries::scheduleToMinuteInterval($token);
			//var_dump($interval);
			if ($interval!==false)
				$intervals[]=$interval;
		}
		return $intervals;
	}
	
	public function getMinuteIntervalsEx() {
		if ($this->schedule==='-') return [];
		$tokens=explode(',',$this->schedule);
		$intervals=[];
		foreach ($tokens as $token) {
			$interval=SchedulesEntries::scheduleExToMinuteInterval($token);
			//var_dump($interval);
			if ($interval!==false)
				$intervals[]=$interval;
		}
		return $intervals;
	}
	
	/**
	 * Возвращает периоды минут входящие в текущий день в рабочем виде
	 * [22:00-06:00] -> [22:00-23:59] вместе с метаданными
	 * @return array
	 */
	public function getDayFitMinuteIntervalsEx() {
		$fit=[];
		foreach ($this->getMinuteIntervalsEx() as $period) {
			if (is_array($period=SchedulesEntries::scheduleMinuteIntervalFitDay($period)))
				$fit[]=$period;
		}
		return $fit;
	}
	
	/**
	 * Возвращает периоды минут переходящие на следующий день (вообще максимум один такой может быть) в рабочем виде
	 * [22:00-06:00] -> [00:00-06:00] вместе с метаданными
	 * @return array
	 */
	public function getOverheadMinuteIntervalsEx() {
		$overhead=[];
		foreach ($this->getMinuteIntervalsEx() as $period) {
			if (is_array($period=SchedulesEntries::scheduleMinuteIntervalOverheadDay($period)))
				$overhead[]=$period;
		}
		return $overhead;
	}
	
	/**
	 * Возвращает кусок расписания выползающее на следующий день в текстовом (рабочем) виде
	 * [22:00-06:00] -> [00:00-06:00] вместе с метаданными
	 * @return string
	 */
	public function getOverheadSchedule() {
		$tokens=[];
		foreach ($this->getOverheadMinuteIntervalsEx() as $overheadMinuteIntervalEx) {
			if($schedule=SchedulesEntries::minuteIntervalToSchedule($overheadMinuteIntervalEx)) $tokens[]=$schedule;
		}
		return implode(',',$tokens);
	}
	
	/**
	 * Возвращает периоды минут  в рабочем виде [00:00-06:00,22:00-23:59] вместе с метаданными
	 * - с учетом предыдущего дня (должен быть проставлен в поле previousDateEntry
	 */
	public function getWorkMinuteIntervalsEx() {
		$work=[];
		//рабочие периоды на сегодня
		foreach ($this->getDayFitMinuteIntervalsEx() as $interval)
			$work[]=$interval;
		//если знаем расписание на вчера
		if (is_object($this->previousDateEntry)) {
			//вылазящие со вчера на сегодня периоды
			foreach ($this->previousDateEntry->getOverheadMinuteIntervalsEx() as $interval)
				$work[]=$interval;
		}
		//обрезаем интервалы в точках пересечений (откидываем куски заползающие вправо на следующий интервал)
		$work=TimeIntervalsHelper::intervalTile($work);
		return $work;
	}
	
	/**
	 * Расписание в текстовом рабочем виде
	 * берет текущее расписание [22:00-06:00], вчерашнее [23:00-03:00] и возвращает [00:00-03:00,22:00-23:59]
	 * в случае пустого расписания будет пустая строка а не - (прочерк)
	 */
	public function getWorkSchedule() {
		$periods=[];
		foreach ($this->getWorkMinuteIntervalsEx() as $interval)
			$periods[]=SchedulesEntries::minuteIntervalToSchedule($interval);
		return implode(',',$periods);
	}
}