<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "schedules".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property string $description
 *
 * @property Services[] $providingServices
 * @property Services[] $supportServices
 * @property Schedules $parent
 */
class Schedules extends \yii\db\ActiveRecord
{
	
	public static $title = 'Расписания';
	
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
            [['name'], 'string', 'max' => 32],
            [['description'], 'string', 'max' => 255],
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
	public function getSupportServices()
	{
		return $this->hasMany(Services::className(), ['support_schedule_id' => 'id']);
	}
	
	
	
	/**
	 * Ищет эффективный день. если не задан конкретный - пытается сослаться на день по умолчанию
	 * @param string $day
	 * @return SchedulesEntries|null
	 */
	public function findEffectiveDay(string $day) {
		$schedule=\app\models\SchedulesEntries::findOne([
			'schedule_id'=>$this->id,
			'date'=>$day
		]);
		if (is_object($schedule)) return $schedule;
		
		return \app\models\SchedulesEntries::findOne([
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
	
}
