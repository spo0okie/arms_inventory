<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\TimeIntervalsHelper;
use app\models\traits\ScheduleEntriesModelCalcFieldsTrait;
use Yii;
use yii\validators\DateValidator;

/*
 * результирующее расписание собирается следующим образом:
 * из суммы всех периодов работы (сложение множеств)
 * вычитается (вычитание множеств)
 * сумма всех периодов отключения (сложение множеств)
 *
 * Для удобства вероятно надо завести словарь для Label-ов и Hint-ов полей для разных сценариев применения графика
 *
 * Известные сценарии:
 * 
 */


/**
 * This is the model class for table "schedules_days".
 *
 * @property int $id
 * @property int|null $schedule_id
 * @property string|null $date
 * @property string|null $date_end
 * @property string|null $schedule
 * @property string|null $mergedSchedule
 * @property string|null $periodSchedule
 * @property string|null $scheduleWithoutMetadata
 * @property string|null $description
 * @property string|null $comment
 * @property string|null $history
 * @property string|null $day
 * @property string|null $dayFor
 * @property string|null $created_at
 * @property string|null $is_period
 * @property string|null $is_work
 * @property string|null $isWorkDescription
 * @property string|null $cellClass
 * @property array $periodInterval
 * @property array $schedulePeriods
 * @property array $minuteIntervals
 * @property array $minuteIntervalsEx
 * @property boolean isAcl
 *
 * @property Schedules $master
 */
class SchedulesEntries extends ArmsModel
{
	use ScheduleEntriesModelCalcFieldsTrait;
	const SCENARIO_PERIOD='scenario_period';
	const SCENARIO_DAY='scenario_day';
	static $title='Период расписания';
	static $titles='Периоды расписаний';
	
	public static $days=[
		'def' => "По умолч.",
		'1' => "Пн",
		'2' => "Вт",
		'3' => "Ср",
		'4' => "Чт",
		'5' => "Пт",
		'6' => "Сб",
		'7' => "Вс",
	];
	
	public static $daysFor=[
		'def' => "по умолч.",
		'1' => "на пн",
		'2' => "на вт",
		'3' => "на ср",
		'4' => "на чт",
		'5' => "на пт",
		'6' => "на сб",
		'7' => "на вс",
	];
	
	public static $isWorkComment=[
		'default'=>[
			0=>'нерабочий период',
			1=>'рабочий период',
		],
		'acl'=>[
			0=>'доступ отозван',
			1=>'доступ предоставляется',
		]
	];
	
	public static $label_day='День';
	public static $label_schedule='График';
	public static $label_graph='Картина дня';
	

	
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'schedules_entries';
	}
	
	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ
	 * @param $time
	 * @return boolean
	 */
	public static function validateTime($time)
	{
		$time=trim($time);
		
		if (!strlen($time)) return false; //пустое время
		
		$tokens=explode(':',$time);
		
		if (count($tokens)!==2) return false; //ожидаем именно ЧЧ:ММ , т.е. токенов 2 и никак иначе

		foreach ($tokens as $token) if (strlen(trim($token))>2) return false; //никаких ЧЧЧ или МММ
		
		if ((int)$tokens[0]>23) return false; //ограничение часов сверху
		if ((int)$tokens[1]>59) return false; //ограничение минут сверху
		
		return true;
	}
	
	public static function scheduleWithoutMetadata($schedule)
	{
		return preg_replace('/{[^}]*}/','',$schedule);
	}
	
	public static function periodMetadata($period)
	{
		preg_match('/{.*}/',$period,$matches);
		if (count($matches)) return $matches[0];
		return false;
	}
	
	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ-ЧЧ:ММ{some_metadata}
	 * @param $schedule
	 * @return boolean
	 */
	public static function validateSchedule($schedule)
	{
		$schedule=trim($schedule);
		
		if (!strlen($schedule)) return false; //пустое расписание
		
		//удаляем метаданные из валидации
		// (нет у меня пока понимания что там может быть, потому считаем что там может быть что угодно)
		$schedule=static::scheduleWithoutMetadata($schedule);
		
		//далее проверяем что расписание вида ЧЧ:MM-ЧЧ:ММ
		
		$tokens=explode('-',$schedule);
		
		if (count($tokens)!==2) return false; //ожидаем именно ЧЧ:ММ-ЧЧ:ММ, т.е. токенов 2 и никак иначе
		
		foreach ($tokens as $token)
			if (!static::validateTime($token)) return false; //проверяем каждый токен на формат ЧЧ:ММ
		
		return true;
	}
	
	
	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ
	 * @param $schedules
	 * @return boolean
	 */
	public static function validateSchedules($schedules)
	{
		$schedules=trim($schedules);
		
		if (!strlen($schedules)) return false; //пустое расписание
		
		if ($schedules==='-') return true; //валидная запись "нерабочий день"
		
		//далее проверяем что расписание вида ЧЧ:MM-ЧЧ:ММ
		
		$tokens=explode(',',$schedules);
		
		if (!count($tokens)) return false; //ожидаем не меньше одного токена
		
		foreach ($tokens as $token)
			if (!static::validateSchedule($token)) return false; //проверяем каждый токен на формат ЧЧ:ММ
		
		return true;
	}
	
	
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['schedule_id'], 'required'],
			[['schedule_id','is_period','is_work'], 'integer'],
			['is_period','default','value'=>0],
			['is_work','default','value'=>1],
			[['created_at','history'], 'safe'],
			[['comment','schedule'], 'string', 'max' => 255],
			[['date','date_end'],'string','max' => 64],
			
			//если у нас "расписание на день, а не период"
			//нужна дата расписания
			['date',function ($attribute) {
				if (isset(SchedulesEntries::$days[$this->date])) return;
				$dateValidator=new DateValidator(['format'=>'php:Y-m-d']);
				if (!$dateValidator->validate($this->date, $error)) {
					$this->addError($attribute, $error.': '.$this->date);
					return; // stop on first error
				}
				if (is_object($entry=$this->master->getDayEntry($this->date)) && ($entry->id!=$this->id)) {
					$this->addError($attribute, 'Расписание на этот день уже внесено');
				}
			}, 'on'=>static::SCENARIO_DAY],
			//и нужно расписание
			['schedule', 'required', 'on'=>static::SCENARIO_DAY],
			['schedule', function ($attribute) {
				if (!strlen(trim($this->schedule))) { //если расписания нет - ругаемся
					$this->addError($attribute, "Необходимо указать расписание на этот день");
				} else { //если расписание есть, проверяем его валидность
					if (!static::validateSchedules($this->schedule))
						$this->addError($attribute, "Неправильный формат(синтаксис) расписания на день");
				}
			},'on'=>static::SCENARIO_DAY],
			
			//если у нас период, а не расписание на день
			//то нужно начало и конец периода в нужных форматах
			[['date','date_end'],'required',
				'message' => 'У периода должна быть хотя бы одна граница',
				'on'=>static::SCENARIO_PERIOD,
				'when'=>function ($model) {return empty($model->date) && empty($model->date_end);},
				'enableClientValidation' => false,
			],
			[['date','date_end'],'date','format'=>'php:Y-m-d H:i:s', 'on'=>static::SCENARIO_PERIOD,],
			[['date','date_end'],function ($attribute)
				{
					if (!empty($this->date) && !empty($this->date_end)) {
						if ($this->periodInterval[0] > $this->periodInterval[1]) {
							$this->addError($attribute, 'Окончание периода должно быть позже его начала');
						}
					}
				},
				'on'=>static::SCENARIO_PERIOD,
			],
			[['date','date_end'],function ($attribute) {
				if (is_object($this->master)) {
					if (is_array($this->master->periods)) foreach ($this->master->periods as $period)
						if ($period->id!=$this->id && $this->periodsIntersect($period)) {
							$this->addError($attribute, 'Пересекается с периодом '.$period->periodSchedule);
						}
				}
			},'on'=>static::SCENARIO_PERIOD],
			
		];
	}
	
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'schedule_id' => [
				'Расписание',
				'hint' => 'Расписание, к которому относится запись',
			],
			'is_period' => [
				'Период',
				'hint' => 'Запись - расписание на день, или более длительный период?',
			],
			'is_work' => [
				'Тип периода',
				'hint' => $this->isAcl?
					'Если установлено - права предоставляются, иначе - отзываются':
					'Если установлено - период рабочий, иначе - нерабочий',
			],
			'is_work_Y' => $this->isAcl?'Доступ предоставляется':'Рабочий период',
			'is_work_N' => $this->isAcl?'Доступ запрещается':'Нерабочий период',
			
			'date' => [
				$this->is_period?'Начало':'День/Дата',
				'hint' => $this->is_period?'Дата/время начала периода':'День/Дата',
			],
			'day' => static::$label_day,
			'date_end' => [
				'Окончание',
				'hint' => 'Дата/время окончания периода',
			],
			'schedule' => [
				static::$label_schedule,
				'hint' => 'График работы/отключения в формате "ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ", или прочерк (минус) для выходного.<br />Примеры/заготовки: '.static::scheduleSamplesHtml(),
			],
			'graph' => static::$label_graph,
			
			'comment' => [
				'Комментарий',
				'hint' => 'Отображается в общем списке',
			],
			'history' => [
				'Дополнительные заметки',
				'hint' => 'Чтобы увидеть надо будет провалиться в запись или навести мышь',
			],
		]);
	}
	
	static function strToUnixTime($time) {
		if (is_null($time) || !strlen($time)) return null;
		return strtotime($time);
	}
	
	
	public function getMaster() {
		return Schedules::findOne($this->schedule_id);
	}
	
	/**
	 * @param SchedulesEntries $period
	 * @return bool
	 */
	public function periodsIntersect($period) {
		if (!$this->is_period || !$period->is_period) return false;
		return TimeIntervalsHelper::intervalIntersect($this->periodInterval,$period->periodInterval);
	}
	
	/**
	 * Возвращает границы интервала в формате [unixtime1,unixtime2]
	 * @return array
	 */
	public function getPeriodInterval() {
		return [
			static::strToUnixTime($this->date),
			static::strToUnixTime($this->date_end),
		];
	}
	
	/**
	 * возвращает рабочие интервалы времени на указанную дату
	 * или кусок периода попадающий в эту дату
	 * или куски рабочего времени по графику
	 * @param $date
	 * @return array|null
	 */
	public function getIntervals($date) {
		if ($this->is_period) {
			return [TimeIntervalsHelper::intervalCut(
				$this->periodInterval,
				[strtotime($date.' 00:00:00'),strtotime($date.' 23:59:59')]
			)];
		} else {//если у нас это расписание на день
			//если мы знаем расписание на предыдущий день
			$intervals=[];
			$workSchedule=$this->getWorkSchedule();
			foreach (explode(',',$workSchedule) as $period) {
				if (is_array($interval=SchedulesEntries::scheduleExToInterval($period,$date)))
					$intervals[]= $interval;
			}
			return $intervals;
		}
	}
	
	/**
	 * считает сколько минут от начала суток в записи HH:MM
	 * @param $timestamp
	 * @return false|int
	 */
	public static function strTimestampToMinutes($timestamp) {
		$tokens=explode(':',$timestamp);
		
		if (count($tokens)===3) unset($tokens[2]); //выкидываем секунды если вдруг они есть
		
		if (count($tokens)!==2) return false; //что-то пошло не так
		
		return 60*(int)$tokens[0]+(int)$tokens[1];
	}
	
	
	/**
	 * конвертирует минуты в запись HH:MM
	 * @param $minutes
	 * @return string
	 */
	public static function intMinutesToStrTimestamp($minutes)
	{
		return
			str_pad(intdiv($minutes,60),2,'0',STR_PAD_LEFT).
			':'.
			str_pad($minutes % 60,2,'0',STR_PAD_LEFT);
	}
	
	/**
	 * Конвертирует HH:MM-HH:MM в [минуты начала,минуты окончания]
	 * @param $schedule
	 * @return false|int[]
	 */
	public static function scheduleToMinuteInterval($schedule) {
		//var_dump($schedule);
		//синтаксические ошибки в периоде
		if (count($tokens=explode('-',$schedule))!==2) return false;
		if (
			($start=static::strTimestampToMinutes($tokens[0]))===false
			||
			($end=static::strTimestampToMinutes($tokens[1]))===false
		) return false; //синтаксические ошибки во временных отметках
		
		return [$start,$end];
	}
	
	/**
	 * Проверяет, что интервал не вылазит на следующий день (например 22:00-01:00)
	 * И если вылазит, то отдает только кусок на день расписания в формате [минуты начала,минуты окончания]
	 * @param $interval
	 * @return array
	 */
	public static function scheduleMinuteIntervalFitDay($interval) {
		if ($interval[0]>$interval[1]) {
			//возвращаем исходный интервал с замененными границами
			return [$interval[0],24*60-1]+$interval;	//[22:00-23:59]]
		}
		return $interval;
	}
	
	/**
	 * Проверяет, что интервал не вылазит на следующий день (например 22:00-01:00)
	 * И если вылазит, то отдает только кусок на следующий день расписания в формате [минуты начала,минуты окончания]
	 * @param $interval
	 * @return array
	 */
	public static function scheduleMinuteIntervalOverheadDay($interval) {
		if ($interval[0]>$interval[1]) {
			//возвращаем исходный интервал с замененными границами
			return [0,$interval[1]]+$interval;	//[00:00-01:00]]
		}
		return null; //нет оверхеда
	}
	
	/**
	 * Конвертирует HH:MM-HH:MM в [минуты начала,минуты окончания,{metadata}]
	 * @param $schedule
	 * @return false|array
	 */
	public static function scheduleExToMinuteInterval($schedule) {
		$metadata=self::periodMetadata($schedule);
		$schedule=self::scheduleWithoutMetadata($schedule);
		if (count($tokens=explode('-',$schedule))!==2) return false;
		if (
			($start=static::strTimestampToMinutes($tokens[0]))===false
			||
			($end=static::strTimestampToMinutes($tokens[1]))===false
		) return false; //синтаксические ошибки во временных отметках
		
		return [$start,$end,'meta'=>$metadata];
	}
	
	/**
	 * Конвертирует HH:MM-HH:MM в [unixtime начала,unixtime окончания,{metadata}]
	 * @param $schedule
	 * @param $date
	 * @return false|array
	 */
	public static function scheduleExToInterval($schedule,$date) {
		$metadata=self::periodMetadata($schedule);
		$schedule=self::scheduleWithoutMetadata($schedule);
		if (count($tokens=explode('-',$schedule))!==2) return false;
		if (
			($start=static::strToUnixTime($date.' '.$tokens[0]))===false
			||
			($end=static::strToUnixTime($date.' '.$tokens[1]))===false
		) return false; //синтаксические ошибки во временных отметках
		
		return [$start,$end,'meta'=>$metadata];
	}
	
	/**
	 * Конвертирует [минуты начала,минуты окончания,{meta}] в HH:MM-HH:MM{meta}
	 * @param $interval array
	 * @return string
	 */
	public static function minuteIntervalToSchedule(array $interval) {
		//синтаксические ошибки в периоде
		if (!isset($interval[0]) && !isset($interval[1])) return '';
		
		return
			self::intMinutesToStrTimestamp($interval[0])
			.'-'
			.self::intMinutesToStrTimestamp($interval[1])
			.(isset($interval['meta'])?$interval['meta']:'');
	}
	
	/**
	 * Конвертирует HH:MM-HH:MM в [минуты начала,минуты окончания]
	 * @param $interval array
	 * @return string
	 */
	public static function unixIntervalToSchedule(array $interval) {
		//синтаксические ошибки в периоде
		if (count($interval)<2) return '';
		
		return
			date('H:i',$interval[0])
			.'-'.
			date('H:i',$interval[1]).
			(
				isset($interval['meta']) && $interval['meta']?
				$interval['meta']:''
			);
			
	}
	

	
	

	
	

	
	public function beforeValidate()
	{
		//корректируем сценарии перед валидацией
		$this->scenario=($this->is_period)?static::SCENARIO_PERIOD:static::SCENARIO_DAY;
		return parent::beforeValidate();
	}
	
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (!strlen($this->date)) $this->date=null;
			if (!strlen($this->date_end)) $this->date_end=null;
			return true;
		} else return false;
	}
	
	/**
	 * Формирует кусок кода для Hint поля $field где должно быть расписание
	 * @param $field
	 * @return string
	 */
	public static function scheduleSamplesHtmlFor($field) {
		return <<<HTML
		<span class="href" onclick="$('#$field').val('-')">отсутствует</span> /
		<span class="href" onclick="$('#$field').val('00:00-23:59')">круглосуточно</span> /
		<span class="href" onclick="$('#$field').val('08:00-17:00')">c 8 до 17</span> /
		<span class="href" onclick="$('#$field').val('08:00-12:00,12:45-17:00')">с обедом в 12ч</span>
HTML;
	}
	
	/**
	 * то же что выше, но для стандартной формы редактирования SchedulesItem
	 * @return string
	 */
	public static function scheduleSamplesHtml() {
		return self::scheduleSamplesHtmlFor('schedulesentries-schedule');
	}
	
	function getCellClass(){
		if (!empty($this->date) && ($this->date == Yii::$app->request->get('date')))
			return 'table-success';
		
		if (is_array($negative=Yii::$app->request->get('negative'))) {
			if (in_array($this->id,$negative))
				return 'table-danger';
		}
		
		if (is_array($positive=Yii::$app->request->get('positive'))) {
			if (in_array($this->id,$positive))
				return 'table-info';
		}
		
		return '';
	}
	
	public function getName() {
		if ($this->is_period) {
			return $this->periodSchedule;
		} else {
			return $this->mergedSchedule;
		}
	}
	
}
