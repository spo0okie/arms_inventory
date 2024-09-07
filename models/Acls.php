<?php

namespace app\models;



use app\models\traits\AclsModelCalcFieldsTrait;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "acls".
 *
 * @property int $id
 * @property int $schedules_id
 * @property int $services_id
 * @property int $networks_id
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
 * @property Networks	$network
 * @property Services	$service
 * @property Aces[]		$aces
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $partners
 * @property Segments[]	$segments
 */
class Acls extends ArmsModel
{

	use AclsModelCalcFieldsTrait;
	
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
	
	public $linksSchema=[
		'aces_ids'=>[Aces::class,'acls_id'],
		'schedules_id'=>[Schedules::class,'acls_ids'],
		'services_id'=>[Services::class,'acls_ids'],
		'networks_id'=>[Networks::class,'acls_ids'],
		'ips_id'=>[NetIps::class,'acls_ids'],
		'comps_id'=>[Comps::class,'acls_ids'],
		'techs_id'=>[Techs::class,'acls_ids'],
	];

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
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['schedules_id', 'services_id', 'ips_id', 'comps_id', 'techs_id','networks_id'], 'integer'],
            [['notepad'], 'string'],
            [['comment'], 'string', 'max' => 255],
			[['services_id', 'ips_id', 'comps_id', 'techs_id','networks_id','comment'],
				'validateRequireOneOf',
				'skipOnEmpty' => false,
				'params'=>['attrs'=>['services_id', 'ips_id', 'comps_id', 'techs_id','networks_id','comment']]
			]
			
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'schedules_id' => 'Расписание доступа',
            'services_id' => ['Сервис','К какому сервису нужно предоставить доступ (включая дочерние сервисы)'],
			'ips_id' => ['IP адрес','IP адрес к которому предоставляется доступ',],
			'networks_id' => ['IP сеть','IP сеть к которой предоставляется доступ',],
            'comps_id' => ['ОС','Имя компьютера (Операционной Системы) к которому предоставляется доступ'],
            'techs_id' => ['Оборудование','Инвентарный номер оборудования к которому предоставляется доступ'],
            'comment' => ['Описание','Описание ресурса к которому предоставляется доступ (просто текст без привязки к объекту БД)'],
            'notepad' => 'Записная книжка',
			'aces' => ['ACEs','indexHint'=>'Access Control Entries <br> (Записи кому предоставляется какой доступ)']
        ];
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

	public function getNetwork() {
		return $this->hasOne(Networks::class, ['id' => 'networks_id'])
			->from(['networks_resources'=>Networks::tableName()]);
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