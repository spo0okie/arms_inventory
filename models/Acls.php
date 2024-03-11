<?php

namespace app\models;



use voskobovich\linker\LinkerBehavior;
use yii\helpers\ArrayHelper;

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
 * @property Schedules	$schedule
 * @property Comps		$comp
 * @property Techs		$tech
 * @property NetIps		$ip
 * @property Services	$service
 * @property Aces[]		$aces
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $partners
 * @property Segments[]	$segments
 */
class Acls extends ArmsModel
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
	
	private $snameCache=null;
	private $departmentsCache=null;
	private $segmentsCache=null;
	private $sitesCache=null;
	private $partnersCache=null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'acls';
    }
	
	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'schedule',
			'aces'
		]);
	}
	/**
	 * В списке поведений прикручиваем many-to-many связи
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'aces_ids' => 'aces', //это не many-2-many. Мне просто нужно _ids поле
				]
			]
		];
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
    public function attributeData()
    {
        return [
            'schedules_id' => 'Расписание доступа',
            'services_id' => ['Сервис','К какому сервису нужно предоставить доступ'],
            'ips_id' => ['IP адрес','IP адрес к которому предоставляется доступ',],
            'comps_id' => ['ОС','Имя компьютера (Операционной Системы) к которому предоставляется доступ'],
            'techs_id' => ['Оборудование','Инвентарный номер оборудования к которому предоставляется доступ'],
            'comment' => ['Описание','Описание ресурса к которому предоставляется доступ (просто текст без привязки к объекту БД)'],
            'notepad' => 'Записная книжка',
			'aces' => ['ACEs','indexHint'=>'Access Control Entries <br> (Записи кому предоставляется какой доступ)']
        ];
    }
    
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		if (!is_null($this->snameCache)) return $this->snameCache;
		if (strlen($this->comment))
			$this->snameCache=$this->comment;
		elseif (($this->comps_id) and is_object($this->comp))
			$this->snameCache= $this->comp->renderName();
		elseif (($this->techs_id) and is_object($this->tech))
			$this->snameCache=$this->tech->num;
		elseif (($this->services_id) and is_object($this->service))
			$this->snameCache=$this->service->name;
		elseif (($this->ips_id) and is_object($this->ip))
			$this->snameCache=$this->ip->sname;
		else
			$this->snameCache=static::$emptyComment;
		
		return $this->snameCache;
	}
	
	public function getName(){return $this->sname;}
	
	/**
	 * организации получающие доступ
	 * @return array
	 */
	public function getPartners() {
		if (!is_null($this->partnersCache)) return $this->partnersCache;

		$this->partnersCache=[];
		if (is_array($this->aces))
			foreach ($this->aces as $ace)
				$this->partnersCache=\app\helpers\ArrayHelper::recursiveOverride(
					$this->partnersCache,
					$ace->partners
				);
		
		return $this->partnersCache;
	}
	
	/**
	 * подразделения получающие доступ
	 * @return array
	 */
	public function getDepartments() {
		if (!is_null($this->departmentsCache)) return $this->departmentsCache;

		$this->departmentsCache=[];
		if (is_array($this->aces))
			foreach ($this->aces as $ace)
				$this->departmentsCache=\app\helpers\ArrayHelper::recursiveOverride(
					$this->departmentsCache,
					$ace->departments
				);
		
		return $this->departmentsCache;
	}
	
	/**
	 * Площадки расположения ресурсов
	 * @return Places[]
	 */
	public function getSites() {
		if (!is_null($this->sitesCache)) return $this->sitesCache;
		if (
			is_object($this->comp) &&
			is_object($this->comp->arm) &&
			is_object($this->comp->arm->place)
		) {
			$this->sitesCache=[$this->comp->arm->place->top];
		} elseif (
			is_object($this->ip) &&
			is_object($this->ip->place)
		) {
			$this->sitesCache=[$this->ip->place->top];
		} elseif (
			is_object($this->tech)
		) {
			$this->sitesCache=[$this->tech->effectivePlace];
		} elseif (is_object($this->service)) {
			$this->sitesCache=$this->service->sitesRecursive;
		} else
			$this->sitesCache=[];
		return $this->sitesCache;
	}
	
	public function getSegments() {
		if (!is_null($this->segmentsCache)) return $this->segmentsCache;
		if (is_object($this->comp)) {
			$this->segmentsCache=$this->comp->segments;
		} elseif (is_object($this->ip)) {
			$this->segmentsCache=[$this->ip->segment];
		} elseif (is_object($this->tech)) {
			$this->segmentsCache=$this->tech->segments;
		} elseif (is_object($this->service)) {
			$this->segmentsCache=[$this->service->segmentRecursive];
		} else {
			$this->segmentsCache=[];
		}
		return $this->segmentsCache;
	}
	
	
	
	public function getAccessTypes() {
		if (!is_array($this->aces)) return [];
		$types=[];
		foreach ($this->aces as $ace) {
			$types=\app\helpers\ArrayHelper::recursiveOverride($types,$ace->accessTypesUniq);
		}
		return $types;
	}
	
	public function getSchedule() {
		return $this->hasOne(Schedules::class, ['id' => 'schedules_id']);
	}
	
	public function getService() {
		return $this->hasOne(Services::class, ['id' => 'services_id'])
			->from(['services_resources'=>Services::tableName()]);
	}
	public function getComp() {
		return $this->hasOne(Comps::class, ['id' => 'comps_id'])
			->from(['comps_resources'=>Comps::tableName()]);
	}
	
	public function getTech() {
		return $this->hasOne(Techs::class, ['id' => 'techs_id'])
			->from(['techs_resources'=>Techs::tableName()]);
	}
	
	public function getIp() {
		return $this->hasOne(NetIps::class, ['id' => 'ips_id'])
			->from(['ips_resources'=>NetIps::tableName()]);
	}
	
	public function getAces() {
		return $this->hasMany(Aces::class, ['acls_id' => 'id']);
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
        return ArrayHelper::map($list, 'id', 'sname');
    }
}