<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "acls".
 *
 * @property int $id
 * @property int $schedules_id
 * @property int $services_id
 * @property int $ips_id
 * @property int $comps_id
 * @property int $techs_id
 * @property string $comment
 * @property string $notepad
 * @property string sname
 *
 * @property \app\models\Schedules	$schedule
 * @property \app\models\Comps		$comp
 * @property \app\models\Techs		$tech
 * @property \app\models\NetIps		$ip
 * @property \app\models\Services	$service
 * @property \app\models\Aces[]		$aces
 */
class Acls extends \yii\db\ActiveRecord
{

	public static $title='Список доступа';
	public static $titles='Списки доступа';

	/*
	 * Если к расписанию прикрутить ACL - то нужно на него смотреть несколько иначе, это не просто расписание
	 * а расписание предоставления доступа. В таком случае интерфейс немного надо скорректировать
	 */
	public static $scheduleTitles = 'Временные доступы';
	public static $scheduleTitle = 'Временный доступ';
	public static $scheduleNameHint = 'Какое-то название для этого доступа. Например "предоставление доступа ООО Рога и копыта к кластеру VMWare в Нюрнберге"';
	public static $scheduleHistoryHint = 'Подробная информация о предоставлении временного доступа.';
	
	
	public static $emptyComment='Заполни меня';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'acls';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['schedules_id', 'services_id', 'ips_id', 'comps_id', 'techs_id'], 'integer'],
            [['notepad'], 'string'],
            [['comment'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'schedules_id' => 'Расписание',
            'services_id' => 'Сервис',
            'ips_id' => 'IP адрес',
            'comps_id' => 'ОС',
            'techs_id' => 'Оборудование',
            'comment' => 'Описание',
            'notepad' => 'Записная книжка',
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'schedules_id' => 'Расписание',
			'services_id' => 'К какому сервису нужно предоставить доступ',
			'ips_id' => 'IP адрес к которому предоставляется доступ',
			'comps_id' => 'Имя компьютера (Операционной Системы) к которому предоставляется доступ',
			'techs_id' => 'Инвентарный номер оборудования к которому предоставляется доступ',
			'comment' => 'Описание ресурса к которому предоставляется доступ (просто текст без привязки к объекту БД)',
			'notepad' => 'Записная книжка',
		];
	}
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		if (strlen($this->comment))
			return $this->comment;
		
		if (($this->comps_id) and is_object($this->comp))
			return $this->comp->name;
		
		if (($this->techs_id) and is_object($this->tech))
			return $this->tech->num;
		
		if (($this->services_id) and is_object($this->service))
			return $this->service->name;
		
		if (($this->ips_id) and is_object($this->ip))
			return $this->ip->sname;
		
		return static::$emptyComment;
	}
	
	public function getSchedule() {
		return $this->hasOne(Schedules::className(), ['id' => 'schedules_id']);
	}
	
	public function getService() {
		return $this->hasOne(Services::className(), ['id' => 'services_id']);
	}
	public function getComp() {
		return $this->hasOne(Comps::className(), ['id' => 'comps_id']);
	}
	
	public function getTech() {
		return $this->hasOne(Techs::className(), ['id' => 'techs_id']);
	}
	
	public function getIp() {
		return $this->hasOne(NetIps::className(), ['id' => 'ips_id']);
	}
	
	public function getAces() {
		return $this->hasMany(Aces::className(), ['acls_id' => 'id']);
	}
	
	public function beforeDelete()
	{
		if (count($this->aces))
			foreach ($this->aces as $ace) {
				$ace->delete();
			}
		return parent::beforeDelete();
	}
	
	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
}