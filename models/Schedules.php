<?php

namespace app\models;

use Yii;
use yii\data\ArrayDataProvider;

/**
 * Hint: В оформлении расписания надо придерживаться правила, что расписание отвечает на вопрос когда?
 */

/**
 * This is the model class for table "schedules".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $override_id
 * @property string $name
 * @property string $description
 * @property string $history
 * @property string $providingMode
 * @property array $weekWorkTime //массив строк, которые надо соединить (запятыми или переносами строк, чтобы получить график работы)
 * @property array $weekWorkTimeDescription //расписание через запятую
 * @property boolean isAcl
 *
 * @property Services[] $providingServices
 * @property Services[] $supportServices
 * @property Acls[] $acls
 * @property Schedules $parent
 * @property SchedulesEntries $entries
 * @property ArrayDataProvider $WeekDataProvider
 */
class Schedules extends \yii\db\ActiveRecord
{
	
	public static $titles = 'Расписания';
	public static $title  = 'Расписание';
	public static $noData = 'никогда';
	public static $allDaysTitle = 'ежедн.';
	public static $allDayTitle = 'круглосуточно.';
	
	
	public static $dictionary=[
		'usage'=>[
			'acl'=>'Доступ предоставляется',
			'providing'=>'Услуга/сервис предоставляется',
			'support'=>'Услуга/сервис поддерживается',
			'working'=>'Рабочее время'
		],
		'nodata'=>[
			'acl'=>'Доступ не предоставляется никогда',
			'providing'=>'Услуга/сервис не предоставляется никогда',
			'support'=>'Услуга/сервис не поддерживается никогда',
			'working'=>'Рабочее время отсутствует (не работает никогда)'
		],
		'always'=>[
			'acl'=>'Доступ предоставляется всегда',
			'providing'=>'Услуга/сервис предоставляется 24/7 без перерывов',
			'support'=>'Услуга/сервис поддерживается 24/7 без перерывов',
			'working'=>'Рабочее время всегда (работает 24/7)'
		],
		'period_start'=>[
			'acl'=>'Начало периода предоставления доступа (если есть)',
			'providing'=>'Дата начала предоставления услуги (если есть)',
			'support'=>'Дата начала поддержки услуги (если есть)',
			'working'=>'Дата начала действия расписания (если есть)'
		],
		'period_end'=>[
			'acl'=>'Конец периода предоставления доступа (если есть)',
			'providing'=>'Дата окончания предоставления услуги (если есть)',
			'support'=>'Дата окончания поддержки услуги (если есть)',
			'working'=>'Дата окончания действия расписания (если есть)'
		],
		'override_start'=>'Дата начала действия другого расписания (обязательно)',
		'override_end'=>'Дата возвращения расписания к исходному (не обязательно)',
	];
	
	public $isAclCache=null;
	public $metaClasses=[]; //список классов метаданных привязанных к этому расписанию
	private $weekDaysCache=null;
	
	public $defaultItemSchedule;
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schedules';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['name','description','defaultItemSchedule'], 'string', 'max' => 255],
			[['start_date','end_date'], 'string', 'max' => 64],
			[['history'],'safe'],
			[['override_id'],'integer'],
			[['parent_id'], function ($attribute, $params, $validator) {
				$children=[$this->id];
				if (is_object($this->parent) && $this->parent->loopCheck($children)!==false) {
					$chain=[];
					foreach ($children as $id) {
						$child=static::findOne($id);
						$chain[]=$child->name;
					}
					//$chain[]=$this->short;
					$this->addError($attribute,'Ссылка на самого себя: '.implode(' -> ',$chain));
				}
			}],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'parent_id' => 'Исходное расписание',
			'name' => 'Наименование',
			'defaultItemSchedule' => 'Расписание на день',
			'description' => 'Пояснение',
			'history' => 'Заметки',
			'start_date'=>'Дата начала',
			'end_date'=>'Дата окончания',
			'resources' => 'Ресурсы', //для ACLs
			'objects' => 'Субъекты', //для ACLs
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'parent_id' => 'Если указать, то расписание будет повторять исходное, а внесенные дни будут правками поверх исходного. (т.е. в текущее расписание надо будет вносить только отличия от исходного)',
			'name' => 'Как-то надо назвать это расписание',
			'defaultItemSchedule' => 'Позже можно будет уточнить дни недели и отдельные даты',
			'description' => 'Пояснение выводится в списке расписаний',
			'history' => 'В списке расписаний не видны. Чтобы прочитать надо будет проваливаться в расписание',
		];
	}
	
	/**
	 * Проверяем петлю по связи потомок-предок
	 * @param $children integer[]
	 * @return false|int
	 */
	public function loopCheck(&$children)
	{
		//если предок уже встречается среди потомков, то сообщаем его
		if (($loop=array_search($this->id,$children))!==false) {
			$children[]=$this->id;
			return $this->id;
		}
		
		//добавляем себя в цепочку потомков
		$children[]=$this->id;
		
		//если родителей нет - то нет и петли
		if (empty($this->parent_id)) return false;
		
		//спрашиваем у предка
		return $this->parent->loopCheck($children);
	}
	
	public function findDay($day) {
		if (is_null($this->weekDaysCache)) {
			$this->weekDaysCache=['def'=>null,'1'=>null,'2'=>null,'3'=>null,'4'=>null,'5'=>null,'6'=>null,'7'=>null];
			foreach ($this->entries as $entry)
				if (!$entry->is_period)
					$this->weekDaysCache[$entry->date]=$entry;
		}
		if (isset($this->weekDaysCache[$day])) return $this->weekDaysCache[$day];
		return null;
		/*return \app\models\SchedulesEntries::findOne([
			'schedule_id'=>$this->id,
			'date'=>$day
		]);*/
	}
	
	public function getIsAcl() {
		if (is_null($this->isAclCache)) {
			$this->isAclCache=is_array($this->acls) && count($this->acls);
		}
		return $this->isAclCache;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Schedules::className(), ['id' => 'parent_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getProvidingServices()
	{
		return $this->hasMany(Services::className(), ['providing_schedule_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::className(), ['schedules_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupportServices()
	{
		return $this->hasMany(Services::className(), ['support_schedule_id' => 'id']);
	}
	
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
	 * Пробуем вернуть коротко расписание работы на неделю
	 * @return array
	 */
	public function getWeekWorkTime()
	{
		$days=['-','пн','вт','ср','чт','пт','сб','вс'];
		
		$description=[];		//итоговое описание расписания на неделю
		$previousSchedule='-';	//расписание на предыдущий день
		$previousDay=null;		//предыдущий день
		$periodFirstDay=null;	//первый день периода с одинаковым расписанием
		
		for ($i=1;$i<=8;$i++) {
			//описание строится по предыдущим дням, поэтому вылазим за воскресенье, чтобы закрыть описание воскресенья
			$scheduleObj=($i===8)?
				null
				:
				$this->getWeekDayScheduleRecursive($i);
			
			$schedule= is_object($scheduleObj)?
				$scheduleObj->mergedSchedule
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
						$description[]=$previousSchedule.' '.static::$allDaysTitle;						//8:00-17:00 ежедневно
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
	 * @return \yii\data\ArrayDataProvider
	 */
	public function getWeekDataProvider()
	{
		$models=[];
		foreach (\app\models\SchedulesEntries::$days as $day=>$name) {
			$models[$day]=$this->getWeekDayScheduleRecursive($day);
		}
		return new \yii\data\ArrayDataProvider([
			'allModels'=>$models
		]);
	}
	
	public function getWeekWorkTimeDescription() {
		
		if (count($periods=$this->weekWorkTime)) {
			$description=implode(',',$periods);
			if ($description=='00:00-23:59 '.static::$allDaysTitle)
				return $this->getDictionary('always');
			return $this->getDictionary('usage').' '.$description;
		} else
			return $this->getDictionary('nodata');
	}
	
	/**
	 * Возвращает кодовое слово режима использования расписания
	 * @return string
	 */
	public function getProvidingMode() {
		if ($this->isAcl) return 'acl';	//доступ предоставляется
		if (count($this->providingServices)) return 'providing'; //услуга предоставляется
		if (count($this->supportServices)) return 'support'; //услуга поддерживается
		return 'working'; //рабочее время
	}
	
	public function getDictionary($word) {
		if (!isset(static::$dictionary[$word]))
			return $word;
		if (is_array(static::$dictionary[$word]))
			return static::$dictionary[$word][$this->providingMode];
		return static::$dictionary[$word];
	}
	
	/**
	 * Находим исключения в расписании в указанный период
	 * @param $start integer
	 * @param $end    integer
	 * @return array|\yii\db\ActiveRecord[]
	 */
	public function findExceptions($start,$end=null)
	{
		return SchedulesEntries::find()
			->Where(['not',['in', 'date', ['1','2','3','4','5','6','7','def']]])
			->andWhere([
				'schedule_id'=>$this->id,
				'is_period'=>0
			])
			->andWhere(is_null($end)?
			[
				'>=', 'UNIX_TIMESTAMP(date)', $start
			]:
			['and',
				['<=', 'UNIX_TIMESTAMP(date)', $end],
				['>=', 'UNIX_TIMESTAMP(date)', $start],
			])
			->all();

		
	}
	
	/**
	 * Ищем периоды в расписании в указанный период
	 * @param $start
	 * @param $end
	 * @return array|\yii\db\ActiveRecord[]
	 */
	public function findPeriods($start=null,$end=null) {
		$query=\app\models\SchedulesEntries::find()
			->where([
				'schedule_id'=>$this->id,
				'is_period'=>1
			]);
		
		if ($start || $end)
			$query->andWhere(['and',
				[
					'or',
					['<=', 'UNIX_TIMESTAMP(date)', $end],
					['date'=>null]
				],
				[
					'or',
					['>=', 'UNIX_TIMESTAMP(date_end)', $start],
					['date_end'=>null],
				],
			]);

		return $query->all();
		
	}
	
	public function getEntries() {
		return $this->hasMany(SchedulesEntries::className(), ['schedule_id' => 'id']);
		
	}
	
	/**
	 * Ищет эффективный день. если не задан конкретный - пытается сослаться на день по умолчанию
	 * @param string $day
	 * @return SchedulesEntries|null
	 */
	public function findEffectiveDay($day) {
		/*$schedule=\app\models\SchedulesEntries::findOne([
			'is_period'=>0,
			'schedule_id'=>$this->id,
			'date'=>$day
		]);
		if (is_object($schedule)) return $schedule;*/
		
		if (!is_null($schedule=$this->findDay($day))) return $schedule;
		return $this->findDay('def');
		/*return \app\models\SchedulesEntries::findOne([
			'is_period'=>0,
			'schedule_id'=>$this->id,
			'date'=>'def'
		]);*/
	}
	
	public function findEffectiveDescription($day) {
		if (is_object($schedule=$this->findEffectiveDay($day))) {
			return $schedule->description;
		} return 'не задано';
	}
	
	public static function fetchNames(){
		$list= static::find()
			->joinWith('acls')
			->select(['schedules.id','name'])
			->where(['acls.schedules_id'=>null])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
	/**
	 * Ищет график работы на конкретный день учитывая родительские расписания
	 * @param $day
	 * @return SchedulesEntries|null
	 */
	public function getDayScheduleRecursive($day)
	{
		//ищем расписание на этот день недели
		if (!is_null($daySchedule=$this->findDay($day))) {
			//var_dump($daySchedule);
			return $daySchedule;
		}
		
		if (is_object($this->parent)) {
			return $this->parent->getDayScheduleRecursive($day);
		} else {
			return null;
		}
	}
	
	/**
	 * Ищет график работы на конкретный день недели учитывая родительские расписания
	 * @param $weekday
	 * @return SchedulesEntries|null
	 */
	public function getWeekDayScheduleRecursive($weekday)
	{
		
		//ищем расписание на этот день недели
		if (!is_null($daySchedule=$this->getDayScheduleRecursive($weekday))) return $daySchedule;
		
		//если не получилось - ищем расписание на любой день
		return $this->getDayScheduleRecursive('def');
	}
	
	/**
	 * Ищет график работы на конкретную дату учитывая родительские расписания
	 * @param $date
	 * @return SchedulesEntries|null
	 */
	public function getDateScheduleRecursive($date)
	{
		
		//ищем расписание на этот день недели
		if (!is_null($daySchedule=$this->getDayScheduleRecursive($date))) return $daySchedule;
		
		//если не получилось - ищем расписание на эту дату по дню недели
		//выясняем день недели на эту дату
		$words=explode('-',$date);
		if (count($words)<3) return null; //ошибка. передана дата не в формате ГГГГ-ММ-ДД
		$weekday=date('N',mktime(0,0,0,$words[1],$words[2],$words[0]));
		
		return $this->getWeekDayScheduleRecursive($weekday);
	}
	
	/**
	 * формирует расписание на день исходя из графика на день + перекрытия рабочими интервалами + нерабочими
	 * @param $date
	 * @return array|object
	 */
	public function getDateSchedule($date)
	{
		//рабочий график на день
		$objSchedule=$this->getDateScheduleRecursive($date);
		if (!is_object($objSchedule)) {
			$objSchedule=new \app\models\SchedulesEntries([
				'is_period'=>0,
				'schedule'=>'-',
				'date'=>'def'
			]);
		}
		
		//ищем периоды работы/отдыха перекрывающие этот день
		$periods=$this->findPeriods(
			strtotime($date.' 00:00:00'),
			strtotime($date.' 23:59:59')
		);
		
		//если никакие периоды не накладываются то возвращаем обычный график
		if (!count($periods)) return $objSchedule;
		
		//иначе правим исходный график периодами работы не работы
		//формируем интервалы рабочего времени
		
		//рабочие интервалы формируем сначала из графика на день
		$positive=$objSchedule->getIntervals($date);
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
		$positive=static::intervalMerge($positive);
		
		if (count($negative)) {
			//есть нерабочие интервалы - они перекрывают рабочие
			//слепляем в кучку
			$negative=static::intervalMerge($negative);
			//вычитаем нерабочие из рабочих
			$positive=static::intervalsSubtraction($positive,$negative);
		}
		
		//сортируем интервалы
		static::intervalsSort($positive);

		//формируем обратно расписание на день
		$arSchedule=[];
		foreach ($positive as $interval) $arSchedule[]=static::interval2Schedule($interval);
		$strSchedule=count ($arSchedule)?
			implode(',',$arSchedule)
			:
			'-';
		
		//возвращаем комплексный массив данных
		return [
			'schedule' => $strSchedule,
			'day' => $objSchedule,
			'posPeriods' => $posPeriods,
			'negPeriods' => $negPeriods
		];
	}
	
	public function isWorkTime($date,$time)
	{
		$schedule=$this->getDateScheduleRecursive($date);
		if (!is_object($schedule)) return 0;
		$periods=$schedule->schedulePeriods;
		$now=\app\models\SchedulesEntries::strTimestampToMinutes($time);
		foreach ($periods as $period) {
			$interval=\app\models\SchedulesEntries::scheduleExToMinuteInterval($period);
			if (self::intervalCheck($interval,$now)) return	1;
		}
		return 0;
	}
	
	public function metaAtTime($date,$time)
	{
		$schedule=$this->getDateScheduleRecursive($date);
		$periods=$schedule->schedulePeriods;
		$now=\app\models\SchedulesEntries::strTimestampToMinutes($time);
		foreach ($periods as $period) {
			$interval=\app\models\SchedulesEntries::scheduleExToMinuteInterval($period);
			if (self::intervalCheck($interval,$now)) {
				if ($interval['meta']===false) return '{}';
				else return $interval['meta'];
			};
		}
		return '{}';
	}
	
	public function nextExclusionRecursive($date,$time)
	{
		$testTimestamp=strtotime(date($date.' '.$time.':00+0000'));
		
	}
	
	public function nextWorkingMeta($date,$time)
	{
		//если прямо сейчас уже рабочий период, то возвращаем его метаданные
		if (($meta=$this->metaAtTime($date,$time))!=='{}') return $meta;
		
		//что мы собственно делаем дальше
		//перебираем 8 дней начиная с текущего в поисках дня, в котором есть рабочие периоды
		//почему 8, потому что если в неделе есть всего один рабочий период, и он сегодня уже прошел,
		//то он повторится через 7 дней на 8й
		$testDate=strtotime(date($date.' 00:00:00+0000'));
		$testTimestamp=strtotime(date($date.' '.$time.':00+0000'));
		for ($i=0;$i<=7;$i++) {
			$day=gmdate('Y-m-d',$testDate+86400*$i);
			$schedule=$this->getDateScheduleRecursive($day);
			if (count($periods=$schedule->schedulePeriods)) {
				foreach ($periods as $period) {
					$interval=\app\models\SchedulesEntries::scheduleExToMinuteInterval($period);
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
	
	
	// МАТЕМАТИКА ИНТЕРВАЛОВ //
	public static function interval2Schedule($interval)
	{
		return date('H:i',$interval[0]).'-'.date('H:i',$interval[1]);
	}
	
	public static function schedule2Interval($schedule,$date)
	{
		$tokens=explode('-',$schedule);
		return [
			strtotime($date.' '.$tokens[0]),
			strtotime($date.' '.$tokens[1]),
		];
		
	}
	
	/**
	 * обрезает границы интервала $interval так, чтобы они не выходили за рамки $range
	 * @param $interval array
	 * @param $range array
	 * @return array
	 */
	public static function intervalCut($interval,$range)
	{
		//граница NULL означает что с этого края интервал открыт
		if (
			$interval[0]<$range[0]
			||
			is_null($interval[0])
		) $interval[0]=$range[0];
		if (
			$interval[1]>$range[1]
			||
			is_null($interval[1])
		) $interval[1]=$range[1];
		return $interval;
	}
	
	/**
	 * проверка попадания в интервал
	 * @param $interval array
	 * @param $value int
	 * @return bool
	 */
	public static function intervalCheck($interval,$value)
	{
		return $interval[0]<=$value && $interval[1]>=$value;
	}
	
	/**
	 * проверка пересечения интервалов
	 * @param $interval1 array
	 * @param $interval2 array
	 * @return bool
	 */
	public static function intervalIntersect($interval1,$interval2)
	{
		// сортируем интервалы так, чтобы второй был не раньше первого
		if ($interval2[0]<$interval1[0]) {
			$tmp=$interval1;
			$interval1=$interval2;
			$interval2=$tmp;
		}
		//далее у нас точно интервал 1 начинается не позже 2го (одновременно или раньше)
		//значит если второй начинается раньше чем первый заканчивается => они пересекаются
		return $interval2[0]<=$interval1[1];
	}
	
	/**
	 * Сравнивает интервалы
	 * @param $interval1
	 * @param $interval2
	 * @return int 1 - первый позже, -1 - первый раньше, 0 - начинаются одновременно
	 */
	public static function intervalsCompare($interval1,$interval2)
	{
		if ($interval1[0]==$interval2[0]) return 0;
		return  ($interval1[0] > $interval2[0])?1:-1;
	}
	
	public static function intervalsSort(&$intervals)
	{
		usort($intervals,['app\models\Schedules','intervalsCompare']);
	}
	
	/**
	 * Вычитание 2x интервалов
	 * @param $A array Уменьшаемое
	 * @param $B array Вычитаемое
	 * @return array[]
	 */
	public static function intervalSubtraction($A,$B)
	{
		if ($B[0]<=$A[0]) {
			//если вычитаемое начинается раньше
			if ($B[1]>=$A[1]) {
				//и вычитаемое заканчивается позже, то оно уничтожает уменьшаемое
				return [];
			} elseif ($B[1]<=$A[0]) {
				//если вычитаемое заканчивается раньше, то оно не пересекается и не трогает уменьшаемое
				return [$A];
			} else {
				//если начинается раньше начала А и заканчивается раньше конца А, то
				//возвращаем кусок от конца вычитаемого до конца уменьшаемого (остальное вычтено)
				return [
					[$B[1],$A[1]]
				];
			}
		} else {
			//вычитаемое В начинается позже начала уменьшаемого А
			if ($B[0]>=$A[1]) {
				//если вычитаемое начинается после уменьшаемого, то они не пересекаются. А не тронуто
				return [$A];
			} elseif ($B[1]>=$A[1]) {
				//вычитаемое начинается внутри уменьшаемого заканчивается позже (откусывает правый кусок)
				return [
					[$A[0],$B[0]]
				];
			} else {
				//В находится внутри А и режет его на кусочки
				return [
					[$A[0],$B[0]],
					[$B[1],$A[1]]
				];
			}
		}
	}
	
	/**
	 * Вычитает интервалы (массив из массива)
	 * @param $minuend array уменьшаемые
	 * @param $subtrahend array вычитаемые
	 * @return array[]
	 */
	public static function intervalsSubtraction($minuend,$subtrahend)
	{
		//выкусываем из всех уменьшаемых вычитаемые по одному
		
		//перебираем вычитаемые (они не меняются в результате операций)
		foreach ($subtrahend as $sub) {
			$difference=[]; //сюда складываем результат
			//перебираем уменьшаемые (а вот они после каждого вычитания меняются)
			foreach ($minuend as $min) {
				$difference=array_merge($difference,static::intervalSubtraction($min,$sub));
			}
			//меняем исходное уменьшаемое после очередной итерации вычитания
			$minuend=$difference;
		}
		return $minuend;
		
	}
	
	/**
	 * склеивает все пересекающиеся интервалы в массиве
	 * @param $intervals array[]
	 * @return array[]
	 */
	public static function intervalMerge($intervals)
	{
		do {
			$intersect=false; //сначала мы не знаем ни о каких пересечениях
			if (count($intervals)>1) { //если интервалов больше 1
				for ($i=0;$i<count($intervals)-1;$i++) { //сравниваем все интервалы по очереди
					for ($j=$i+1;$j<count($intervals);$j++) {
						if (static::intervalIntersect($intervals[$i],$intervals[$j])) { //если они пересекаются,то
							$intersect=true;
							
							//интервал пересечение;
							$merged=[
								min($intervals[$i][0],$intervals[$j][0]),
								max($intervals[$i][1],$intervals[$j][1])
							];
							//убираем исходные интервалы
							unset($intervals[$i]);
							unset($intervals[$j]);
							
							//добавляем сумму пересечения
							$intervals[]=$merged;
							
							//сбрасываем индексы массива интервалов (reindex)
							$intervals=array_values($intervals);
							
							break 2; //выходим из 2х вложенных for
						}
					}
				}
			}
			
		} while ($intersect);
		return $intervals;
	}
	
	public function beforeDelete()
	{
		if (count($this->acls)) {
			foreach ($this->acls as $acl)
				if (!$acl->delete()) return false;
		}
		return parent::beforeDelete(); // TODO: Change the autogenerated stub
	}
	
	
	
}
