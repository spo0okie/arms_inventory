<?php

namespace app\models;

use Yii;
use yii\web\User;

/**
 * This is the model class for table "services".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property string $description
 * @property int $is_end_user
 * @property int $user_group_id
 * @property int $sla_id
 * @property int $is_service
 * @property string $notebook
 * @property string $links
 * @property int $responsible_id
 * @property string $responsibleName
 * @property int $providing_schedule_id
 * @property int $support_schedule_id
 * @property string $providingScheduleName
 * @property string $supportScheduleName
 * @property int $segment_id
 * @property int $places_id
 * @property int $partners_id
 * @property int $currency_id
 * @property int $archived
 * @property float $cost
 * @property float $charge
 * @property string $segmentName
 * @property int[] $depends_ids
 * @property int[] $comps_ids
 * @property int[] $support_ids
 * @property int[] $techs_ids
 *
 *
 * @property \app\models\Comps[] $comps
 * @property \app\models\Services[] $depends
 * @property \app\models\Services[] $dependants
 * @property \app\models\UserGroups $userGroup
 * @property \app\models\Techs[] $techs
 * @property \app\models\Techs[] $techsRecursive
 * @property \app\models\Arms[] $arms
 * @property \app\models\Arms[] $armsRecursive
 * @property \app\models\Places[] $armPlaces
 * @property \app\models\Places[] $techPlaces
 * @property \app\models\Places[] $sites
 * @property \app\models\Places[] $sitesRecursive
 * @property \app\models\Services $parent
 * @property \app\models\Services[] $children
 * @property Schedules $providingSchedule
 * @property Schedules $providingScheduleRecursive
 * @property Users $responsible
 * @property Users $responsibleRecursive
 * @property Users[] $support
 * @property Users[] $supportRecursive
 * @property Schedules $supportSchedule
 * @property Schedules $supportScheduleRecursive
 * @property Segments $segment
 * @property Segments $segmentRecursive
 * @property Acls[] $acls
 * @property Currency $currency
 * @property Partners $partner
 */
class Services extends \yii\db\ActiveRecord
{
	
	public static $titles='Сервисы/услуги';
	public static $title='Сервис/услуга';

	public static $user_service_title='Сервис для пользователей';
	public static $tech_service_title='Служебный сервис';
	public static $user_job_title='Услуга для пользователей';
	public static $tech_job_title='Служебная услуга';
	
	
	private $segmentRecursiveCache=null;
	private $supportRecursiveCache=null;
	private $responsibleRecursiveCache=null;
	private $providingScheduleRecursiveCache=null;
	private $supportScheduleRecursiveCache=null;
	private $armsRecursiveCache=null;
	private $techsRecursiveCache=null;
	private $placesRecursiveCache=null;
	private $sitesRecursiveCache=null;


	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'depends_ids' => 'depends',
					'comps_ids' => 'comps',
					'support_ids' => 'support',
					'contracts_ids' => 'contracts',
					'techs_ids' => 'techs'
				]
			]
		];
	}

	public function extraFields()
	{
		return [
			'responsibleName',
			'supportScheduleName',
			'providingScheduleName',
			'supportNames',
			'segmentName',
		];
	}
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['currency_id'],'default','value'=>1],
            [['name', 'description', 'is_end_user'], 'required'],
	        [['depends_ids','comps_ids','support_ids','techs_ids','contracts_ids'], 'each', 'rule'=>['integer']],
	        [['description', 'notebook','links'], 'string'],
			[['places_id','partners_id','places_id','archived','currency_id'],'integer'],
			[['cost','charge'], 'number'],
	        [['is_end_user', 'is_service', 'responsible_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
	        [['name'], 'string', 'max' => 64],
	        [['responsible_id'],        'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['responsible_id' => 'id']],
	        [['providing_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schedules::className(), 'targetAttribute' => ['providing_schedule_id' => 'id']],
	        [['support_schedule_id'],   'exist', 'skipOnError' => true, 'targetClass' => Schedules::className(), 'targetAttribute' => ['support_schedule_id' => 'id']],
			[['segment_id'],			'exist', 'skipOnError' => true, 'targetClass' => Segments::className(), 'targetAttribute' => ['segment_id' => 'id']],
			[['parent_id'],				'exist', 'skipOnError' => true, 'targetClass' => Services::className(), 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
			'currency_id' => 'Валюта',
			'name' => 'Название',
			'parent_id' => 'Основной сервис/услуга',
	        'description' => 'Описание',
	        'links' => 'Ссылки',
			'is_service' => 'Тип объекта',
			'is_end_user' => 'Предоставляется пользователям',
            'user_group_id' => 'Группа ответственных',
	        'depends_ids' => 'Зависит от сервисов/услуг',
			'comps' => 'Серверы',
			'comps_ids' => 'Серверы',
			'techs' => 'Оборудование',
			'techs_ids' => 'Оборудование',
	        'providingSchedule' => 'Время предоставления',
	        'providing_schedule_id' => 'Время предоставления',
	        'supportSchedule' => 'Время поддержки',
	        'support_schedule_id' => 'Время поддержки',
	        'responsible' => 'Ответственный, поддержка',
	        'responsible_id' => 'Ответственный',
			'support_ids' => 'Поддержка',
			'contracts_ids' => Contracts::$titles,
            'notebook' => 'Записная книжка',
			'segment_id' => 'Сегмент ИТ',
			'segment' => 'Сегмент ИТ',
			'arms' => Arms::$title,
			'archived' => 'Архивирован',
			'places_id' => Places::$title,
			'places' => Places::$titles,
			'partners_id' => Partners::$title,
			'partner' => Partners::$title,
			'sites' => 'Площадки',
			'cost' => 'Стоимость',
			'charge' => 'НДС',
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'currency_id' => 'Ед. изм. стоим.',
			'name' => 'Короткое уникальное название сервиса или услуги',
			'parent_id' => 'Здесь можно указать в состав какого, более крупного сервиса, входит этот сервис',
			'description' => 'Развернутое название или краткое описание назначения этого сервиса. Все детали тут описывать не нужно. Нужно в поле ниже вставить ссылку на вики страничку с описанием',
			'links' => \app\components\UrlListWidget::$hint.' Нужно обязательно вставить ссылку на вики страничку описания и, если они есть, на странички входа на сервис и поддержки',
			'is_end_user' => 'Предоставляется ли этот сервис пользователям (иначе используется другими сервисами)',
			'user_group_id' => 'Группа сотрудников ответственных за работоспособность сервиса/предоставление услуги',
			'depends_ids' => 'От работы каких сервисов зависит работа этого сервиса/предоставление услуги',
			'comps_ids' => 'На каких серверах выполняется этот сервис/услуге',
			'contract_ids' => 'Документы привязанные к этому сервису/услуге',
			'techs_ids' => 'На каком оборудовании выполняется этот сервис',
			'places_id' => 'Привязать сервис/услугу к помещению. Иначе помещение будет косвенно выясняться на основании расположения серверов и оборудования',
			'partners_id' => 'Если услуга/сервис оказывается каким-либо контрагентом (иначе внутренняя)',
			'notebook' => 'Устаревшее поле. Вся информация должна быть на вики страничке.',
			'support_schedule_id' => 'Расписание, когда нужно реагировать на сбои в работе сервиса',
			'providing_schedule_id' => 'Расписание, когда сервисом могут воспользоваться пользователи или другие сервисы',
			'responsible_id' => 'Ответственный за работу сервиса/оказание услуги',
			'support_ids' => 'Дополнительные члены команды по поддержке сервиса/оказанию услуги',
			'segment_id' => 'Сегмент ИТ инфраструктуры к которому относится этот сервис',
			'archived' => 'Если сервис/услуга более не используется, но для истории его описание лучше сохранить - то его можно просто заархивировать, чтобы не отсвечивал',
			'cost' => 'Стоимость услуги в месяц. (если понадобится другой период - обращайтесь к разработчику)',
			'charge' => 'налог',
			'contracts_ids' => 'Привязанные к услуге документы. Можно привязать только договор, а все счета/акты/доп.соглашения уже привязывать к договору',
		];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::className(), ['id' => 'currency_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::className(), ['id' => 'partners_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupportSchedule()
	{
		return $this->hasOne(Schedules::className(), ['id' => 'support_schedule_id'])
			->from(['support_schedule'=>Schedules::tableName()]);
	}
	
	public function getSupportScheduleRecursive() {
		if (is_object($this->supportScheduleRecursiveCache)) return $this->supportScheduleRecursiveCache;
		if (is_object($this->supportScheduleRecursiveCache = $this->supportSchedule))
			return $this->supportScheduleRecursiveCache;
		if (is_object($this->parent))
			return $this->supportScheduleRecursiveCache=$this->parent->supportScheduleRecursive;
		return null;
	}
	
	public function getSupportScheduleName() {
		if (is_object($this->supportScheduleRecursive)) return $this->supportScheduleRecursive->name;
		return null;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getProvidingSchedule()
	{
		return $this->hasOne(Schedules::className(), ['id' => 'providing_schedule_id'])
			->from(['providing_schedule'=>Schedules::tableName()]);
	}
	
	public function getProvidingScheduleRecursive() {
		if (is_object($this->providingScheduleRecursiveCache)) return $this->providingScheduleRecursiveCache;
		if (is_object($this->providingScheduleRecursiveCache = $this->providingSchedule))
			return $this->providingScheduleRecursiveCache;
		if (is_object($this->parent))
			return $this->providingScheduleRecursiveCache = $this->parent->providingScheduleRecursive;
		return null;
	}
	
	public function getProvidingScheduleName() {
		if (is_object($this->providingScheduleRecursive)) return $this->providingScheduleRecursive->name;
		return null;
	}
	
	/**
	 * @return Segments|\yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Services::className(), ['id' => 'parent_id']);
	}
	
	/**
	 * Непосредственные потомки
	 * @return Segments[]|\yii\db\ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(Services::className(), ['parent_id' => 'id']);
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getResponsible()
	{
		return $this->hasOne(Users::className(), ['id' => 'responsible_id'])->from(['responsible'=>Users::tableName()]);
	}
	
	public function getResponsibleRecursive() {
		if (is_object($this->responsibleRecursiveCache)) return $this->responsibleRecursiveCache;
		if (is_object($this->responsibleRecursiveCache = $this->responsible))
			return $this->responsibleRecursiveCache;
		if (is_object($this->parent))
			return $this->responsibleRecursiveCache = $this->parent->responsibleRecursive;
		return null;
	}

	public function getResponsibleName() {
		if (is_object($this->responsibleRecursive)) return $this->responsibleRecursive->Ename;
		return null;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::className(), ['id' => 'segment_id']);
	}
	
	/**
	 * Возвращает сегмент сервиса с учетом родителей
	 * @return string|null
	 */
	public function getSegmentRecursive() {
		if (is_object($this->segmentRecursiveCache)) return $this->segmentRecursiveCache;
		if (is_object($this->segmentRecursiveCache = $this->segment))
			return $this->segmentRecursiveCache;
		if (is_object($this->parent))
			return $this->segmentRecursiveCache = $this->parent->segment;
		return null;
	}
	
	public function getSegmentName() {
		if (is_object($this->segmentRecursive)) return $this->segmentRecursive->name;
		return null;
	}
	
	/**
	 * Возвращает сервисы от которых зависит этот сервис
	 */
	public function getDepends()
	{
		return static::hasMany(Services::className(), ['id' => 'depends_id'])
			->viaTable('{{%services_depends}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы от которых зависит этот сервис
	 */
	public function getSupport()
	{
		return static::hasMany(Users::className(), ['id' => 'user_id'])
			->from(['support'=>Users::tableName()])
			->viaTable('{{%users_in_services}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает документы привязанные к сервису
	 */
	public function getContracts()
	{
		return static::hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->from(['service_contracts'=>Contracts::tableName()])
			->viaTable('{{%contracts_in_services}}', ['services_id' => 'id']);
	}
	
	public function getSupportRecursive() {
		if (is_array($this->supportRecursiveCache) && count($this->supportRecursiveCache)) {
			return $this->supportRecursiveCache;
		}
		if (is_array($this->supportRecursiveCache = $this->support) && count($this->supportRecursiveCache)){
			//var_dump($this->supportRecursiveCache);
			return $this->supportRecursiveCache;
			
		}
		if (is_object($this->parent))
			return $this->supportRecursiveCache = $this->parent->supportRecursive;
		return [];
	}
	
	public function getSupportNames() {
		$names=[];
		foreach ($this->supportRecursive as $user)
			if (is_object($user)) $names[]=$user->Ename;
			
		if (count($names)) return implode(',',$names);
		return null;
	}
	
	/**
	 * Возвращает сервисы зависимые от этого сервиса
	 */
	public function getDependants()
	{
		return static::hasMany(Services::className(), ['id' => 'service_id'])
			->from(['responsible'=>Services::tableName()])
			->viaTable('{{%services_depends}}', ['depends_id' => 'id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getComps()
	{
		return static::hasMany(Comps::className(), ['id' => 'comps_id'])
			->viaTable('{{%comps_in_services}}', ['services_id' => 'id']);
	}
	
	public function getArmsRecursive()
	{
		if (is_array($this->armsRecursiveCache)) return $this->armsRecursiveCache;
		$this->armsRecursiveCache=$this->getArms()->all();
		if (is_object($this->parent)) {
			array_push($this->armsRecursiveCache,$this->parent->armsRecursive);
		}
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getTechs()
	{
		return static::hasMany(Techs::className(), ['id' => 'tech_id'])
			->viaTable('{{%techs_in_services}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает группу ответственных за сервис
	 */
	public function getUserGroup()
	{
		return static::hasOne(UserGroups::className(), ['id' => 'user_group_id']);
	}
	
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arm_id'])
			->via('comps');
	}
	
	public function getArmPlaces()
	{
		return static::hasMany(Places::className(), ['id' => 'places_id'])
			->from(['arms_places'=>Places::tableName()])
			->via('arms');
	}
	
	public function getTechPlaces()
	{
		return static::hasMany(Places::className(), ['id' => 'places_id'])
			->from(['tech_places'=>Places::tableName()])
			->via('techs');
	}
	
	public function getSites(){
		$sites=[];
		foreach (array_merge($this->techPlaces,$this->armPlaces) as $place) {
			$site=$place->top;
			if (!array_key_exists($site->id,$sites))
				$sites[$site->id]=$site;
		}
		return array_values($sites);
	}

	public function getSitesRecursive(){
		if (is_array($this->sitesRecursiveCache)) return $this->sitesRecursiveCache;
		$sites=[];
		foreach ($this->sites as $site)
			$sites[$site->id]=$site;
		foreach ($this->children as $service)
			foreach ($service->sitesRecursive as $site)
				$sites[$site->id]=$site;
		
		return $this->sitesRecursiveCache = array_values($sites);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::className(), ['services_id' => 'id']);
	}
	
	
	
	/**
	 * @return array
	 */
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])->orderBy('name')
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
}
