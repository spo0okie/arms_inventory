<?php

namespace app\models;

use Yii;

/**
 * Hint: В оформлении расписания надо придерживаться правила, что расписание отвечает на вопрос когда?
 */

/**
 * This is the model class for table "schedules".
 *
 * @property int $id
 * @property int $parent_id
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
	];
	
	public $isAclCache=null;
	
	
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
            [['name','description'], 'string', 'max' => 255],
			[['history'],'safe'],
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
			'description' => 'Пояснение',
			'history' => 'Заметки',
			'monEffectiveDescription' => 'Пон.',
			'tueEffectiveDescription' => 'Втр.',
			'wedEffectiveDescription' => 'Срд.',
			'thuEffectiveDescription' => 'Чтв.',
			'friEffectiveDescription' => 'Пят.',
			'satEffectiveDescription' => 'Суб.',
			'sunEffectiveDescription' => 'Вск.',
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
			'parent_id' => 'Если указать, то расписание будет повторять исходное, а внесенные дни недели и нестандартные дни будут вносить правки поверх исходного. (т.е. в текущее расписание надо будет внести только отличия от исходного)',
			'name' => 'Как-то надо назвать это расписание',
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
		return \app\models\SchedulesEntries::findOne([
			'schedule_id'=>$this->id,
			'date'=>$day
		]);
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
				$scheduleObj->schedule
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
	
	public function getWeekWorkTimeDescription() {
		
		if (count($periods=$this->weekWorkTime)) {
			$description=implode(',',$periods);
			if ($description=='00:00-23:59 '.static::$allDaysTitle)
				$description=$this->getDictionary('always');
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
		return static::$dictionary[$word][$this->providingMode];
	}
	
	/**
	 * Находим исключения в расписании в указанный период
	 * @param $start integer
	 * @param $end    integer
	 * @return array|\yii\db\ActiveRecord[]
	 */
	public function findExceptions($start,$end)
	{
		return SchedulesEntries::find()
			->Where(['not',['in', 'date', ['1','2','3','4','5','6','7','def']]])
			->andWhere([
				'schedule_id'=>$this->id,
				'is_period'=>0
			])
			->andWhere(['and',
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
	
	/**
	 * Ищет эффективный день. если не задан конкретный - пытается сослаться на день по умолчанию
	 * @param string $day
	 * @return SchedulesEntries|null
	 */
	public function findEffectiveDay($day) {
		$schedule=\app\models\SchedulesEntries::findOne([
			'is_period'=>0,
			'schedule_id'=>$this->id,
			'date'=>$day
		]);
		if (is_object($schedule)) return $schedule;
		
		return \app\models\SchedulesEntries::findOne([
			'is_period'=>0,
			'schedule_id'=>$this->id,
			'date'=>'def'
		]);
	}
	
	public function findEffectiveDescription($day) {
		if (is_object($schedule=$this->findEffectiveDay($day))) {
			return $schedule->description;
		} return 'не задано';
	}
	
	public function getMonEffectiveDescription() {
		return $this->findEffectiveDescription('1');
	}
	
	public function getTueEffectiveDescription() {
		return $this->findEffectiveDescription('2');
	}
	
	public function getWedEffectiveDescription() {
		return $this->findEffectiveDescription('3');
	}
	
	public function getThuEffectiveDescription() {
		return $this->findEffectiveDescription('4');
	}
	
	public function getFriEffectiveDescription() {
		return $this->findEffectiveDescription('5');
	}
	
	public function getSatEffectiveDescription() {
		return $this->findEffectiveDescription('6');
	}
	
	public function getSunEffectiveDescription() {
		return $this->findEffectiveDescription('7');
	}
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
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
		if (!is_null($daySchedule=\app\models\SchedulesEntries::findOne([
			'schedule_id'=>$this->id,
			'is_period'=>0,
			'date'=>$day
		]))) return $daySchedule;
		
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
	 * @return string
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
		return $interval2[0]<$interval1[1];
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
