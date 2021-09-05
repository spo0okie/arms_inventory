<?php

namespace app\models;

use Yii;
use yii\validators\DateValidator;

/*
 * результирующее расписание собирается следующим образом:
 * из суммы всех периодов работы (сложение множеств)
 * вычитается (вычитание множеств)
 * сумма всех периодов отключения (сложение множеств)
 */


/**
 * This is the model class for table "schedules_days".
 *
 * @property int $id
 * @property int|null $schedule_id
 * @property string|null $date
 * @property string|null $date_end
 * @property string|null $schedule
 * @property string|null $description
 * @property string|null $comment
 * @property string|null $history
 * @property string|null $day
 * @property string|null $created_at
 * @property string|null $is_period
 * @property string|null $is_work
 * @property string|null $isWorkDescription
 * @property array $interval
 * @property boolean isAcl
 *
 * @property Schedules $master
 */
class SchedulesEntries extends \yii\db\ActiveRecord
{
	
	public static $days=[
		'def' => "По умолч.",
		'1' => "Пон",
		'2' => "Втр",
		'3' => "Срд",
		'4' => "Чтв",
		'5' => "Птн",
		'6' => "Суб",
		'7' => "Вск",
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
	 * @return string|null
	 */
	public function getDay() {
		if (isset(static::$days[$this->date])) return static::$days[$this->date];
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
	
	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ-ЧЧ:ММ
	 * @param $schedule
	 * @return boolean
	 */
	public static function validateSchedule($schedule)
	{
		$schedule=trim($schedule);
		
		if (!strlen($schedule)) return false; //пустое расписание
		
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
			'date_end' => 'Окончание',
			'schedule' => 'График',
			
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
			'schedule' => 'График работы/отключения в формате "ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ" например 8:00-12:00,12:45-17:00, или прочерк (минус) для выходного',
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
			foreach (explode(',',$this->schedule) as $schedule) {
				$intervals[]=\app\models\Schedules::schedule2Interval($schedule,$date);
			};
			return $intervals;
			
		}
		return [];
		
	}
	
	public function getPeriodSchedule() {
		if (!$this->is_period) return null;
		
		if (date('Y-m-d',strtotime($this->date)) == date('Y-m-d',strtotime($this->date_end ))) {
			//начинается и кончается в один день
			return date('Y-m-d',strtotime($this->date)).' '.date('H:i',strtotime($this->date)).'-'.date('H:i',strtotime($this->date));
		} else {
			return
				(is_null($this->date)?'нет начала':date('Y-m-d',strtotime($this->date))).
				' - '.
				(is_null($this->date_end)?'нет конца':date('Y-m-d',strtotime($this->date_end)));
		}
	}
	
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (!strlen($this->date)) $this->date=null;
			if (!strlen($this->date_end)) $this->date_end=null;
			return true;
		} else return false;
	}
	
}
