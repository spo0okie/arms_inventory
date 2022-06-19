<?php

namespace app\models;

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
 * @property array $schedulePeriods
 * @property array $minuteIntervals
 * @property array $minuteIntervalsEx
 * @property boolean isAcl
 *
 * @property Schedules $master
 */
class SchedulesEntries extends \yii\db\ActiveRecord
{
	
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
	

	public $isAclCache=null;
	
		
		
		public function getIsAcl() {
		if (is_null($this->isAclCache)) {
			$this->isAclCache=is_object($this->master) && $this->master->isAcl;
		}
		return $this->isAclCache;
	}
	
	public function getIsWorkDescription() {
		if ($this->isAcl)
			return static::$isWorkComment['acl'][$this->is_work];
		return static::$isWorkComment['default'][$this->is_work];
		
	}
	
	/**
	 * Возвращает описание даты записи
	 * @return string
	 */
	public function getDay() {
		if (isset(static::$days[$this->date])) return static::$days[$this->date];
		return $this->date;
	}
	
	/**
	 * Возвращает описание даты записи
	 * @return string
	 */
	public function getDayFor() {
		if (isset(static::$daysFor[$this->date])) return static::$daysFor[$this->date];
		return $this->date;
	}
	
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
		return preg_replace('/\{[^\}]*\}/','',$schedule);
	}
	
	public static function periodMetadata($period)
	{
		preg_match('/\{.*\}/',$period,$matches);
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
			[['created_at'], 'safe'],
			
			//если у нас "расписание на день, а не период"
			//нужна дата расписания
			['date',function ($attribute, $params, $validator) {
				if (isset(SchedulesEntries::$days[$this->date])) return;
				
				$dateValidator=new DateValidator(['format'=>'php:Y-m-d']);
				if (!$dateValidator->validate($this->date, $error)) {
					$this->addError($attribute, $error.': '.$this->date);
					return; // stop on first error
				}
			}, 'when'=>function ($model) {return !$model->is_period;}],
			//и нужно расписание
			['schedule', 'required', 'when'=>function ($model) {return !$model->is_period;}],
			['schedule', function ($attribute, $params, $validator) {
				if (!strlen(trim($this->schedule))) { //если расписания нет - ругаемся
					$this->addError($attribute, "Необходимо указать расписание на этот день");
				} else { //если расписание есть, проверяем его валидность
					if (!static::validateSchedules($this->schedule))
						$this->addError($attribute, "Неправильный формат(синтаксис) расписания на день");
				}
			}, 'when'=>function ($model) {return !$model->is_period;}],
			
			//если у нас период, а не расписание на день
			//то нужно начало и конец периода в нужных форматах
			[['date','date_end'],'required',
				'message' => 'У периода должна быть хотя бы одна граница',
				'when'=>function ($model) {return $model->is_period && empty($model->date) && empty($model->date_end);},
				'enableClientValidation' => false,
			],
			[['date','date_end'],'date','format'=>'php:Y-m-d H:i:s', 'when'=>function ($model) {return $model->is_period;}],
			
			[['comment'], 'string', 'max' => 255],
			[['history'], 'safe'],
		];
	}
	
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'schedule_id' => 'Расписание',
			'is_period' => 'Период',
			'is_work' => $this->isAcl?'Период предоставления/отзыва прав':'Рабочий/нерабочий период',
			
			'date' => $this->is_period?'Начало':'День/Дата',
			'day' => static::$label_day,
			'date_end' => 'Окончание',
			'schedule' => static::$label_schedule,
			'graph' => static::$label_graph,
			
			'comment' => 'Комментарий',
			'history' => 'Дополнительные заметки',
			'created_at' => 'Создано',
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID записи',
			'schedule_id' => 'Расписание, к которому относится запись',
			'is_period' => 'Запись - расписание на день, или более длительный период?',
			'is_work' => $this->isAcl?'Если установлено - права предоставляются, иначе - отзываются':'Если установлено - период рабочий, иначе - нерабочий',
			
			//'date' => 'День/Дата',
			'date' => $this->is_period?'Дата/время начала периода':'День/Дата',
			//'date_begin' => ,
			'date_end' => 'Дата/время окончания периода',
			'schedule' => 'График работы/отключения в формате "ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ", или прочерк (минус) для выходного.<br />Примеры/заготовки: '.static::scheduleSamplesHtml(),
			//'description' => 'График',
			
			'comment' => 'Отображается в общем списке',
			'history' => 'Чтобы увидеть надо будет провалиться в запись или навести мышь',
			'created_at' => 'Отметка времени создания записи',
		];
	}
	
	public function getMaster() {
		return \app\models\Schedules::findOne($this->schedule_id);
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
			return [\app\models\Schedules::intervalCut(
				[is_null($this->date)?null:strtotime($this->date),is_null($this->date_end)?null:strtotime($this->date_end)],
				[strtotime($date.' 00:00:00'),strtotime($date.' 23:59:59')]
			)];
		} elseif ($this->schedule!=='-') {
			$intervals=[];
			foreach (explode(',',$this->scheduleWithoutMetadata) as $schedule) {
				$intervals[]=\app\models\Schedules::schedule2Interval($schedule,$date);
			};
			return $intervals;
			
		}
		return [];
		
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
	 * Конвертирует HH:MM-HH:MM в [минуты начала,минуты окончания,{metadata}]
	 * @param $schedule
	 * @return false|array
	 */
	public static function scheduleExToMinuteInterval($schedule) {
		//var_dump($schedule);
		//синтаксические ошибки в периоде
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
	 * Конвертирует HH:MM-HH:MM в [минуты начала,минуты окончания]
	 * @param $interval array
	 * @return string
	 */
	public static function minuteIntervalToSchedule(array $interval) {
		//синтаксические ошибки в периоде
		if (count($interval)!==2) return '';
		
		return
			self::intMinutesToStrTimestamp($interval[0])
			.'-'
			.self::intMinutesToStrTimestamp($interval[1]);
	}
	
	public function getSchedulePeriods() {
		if ($this->schedule==='-') return [];
		return explode(',',$this->schedule);
	}
	
	public function getMinuteIntervals() {
		$tokens=explode(',',$this->scheduleWithoutMetadata);
		$intervals=[];
		foreach ($tokens as $token) {
			$interval=static::scheduleToMinuteInterval($token);
			//var_dump($interval);
			if ($interval!==false)
				$intervals[]=$interval;
		}
		return $intervals;
	}
	
	public function getMinuteIntervalsEx() {
		$tokens=explode(',',$this->schedule);
		$intervals=[];
		foreach ($tokens as $token) {
			$interval=static::scheduleExToMinuteInterval($token);
			//var_dump($interval);
			if ($interval!==false)
				$intervals[]=$interval;
		}
		return $intervals;
	}
	
	public function getPeriodSchedule() {
		if (!$this->is_period) return null;
		
		if (date('Y-m-d',strtotime($this->date)) == date('Y-m-d',strtotime($this->date_end ))) {
			//начинается и кончается в один день
			return date('Y-m-d',strtotime($this->date)).' '.date('H:i',strtotime($this->date)).'-'.date('H:i',strtotime($this->date_end));
		} else {
			return
				(is_null($this->date)?'нет начала':date('Y-m-d',strtotime($this->date))).
				' - '.
				(is_null($this->date_end)?'нет конца':date('Y-m-d',strtotime($this->date_end)));
		}
	}
	
	public function getScheduleWithoutMetadata() {
		return static::scheduleWithoutMetadata($this->schedule);
	}
	
	public function getMergedSchedule() {
		if ($this->schedule === '-') return '-';
		$intervals=\app\models\Schedules::intervalMerge($this->minuteIntervals);
		//var_dump($intervals);
		$timestamps=[];
		foreach ($intervals as $interval)
			$timestamps[]=static::minuteIntervalToSchedule($interval);
		return implode(',',$timestamps);
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
	
	
}
