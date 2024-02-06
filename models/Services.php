<?php

namespace app\models;

use app\components\UrlListWidget;
use app\helpers\ArrayHelper;
use app\helpers\QueryHelper;
use voskobovich\linker\LinkerBehavior;
use yii\db\ActiveQuery;

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
 * @property int $infrastructure_user_id
 * @property string $infrastructureResponsibleName
 * @property int $providing_schedule_id
 * @property int $support_schedule_id
 * @property string $providingScheduleName
 * @property string $supportScheduleName
 * @property int $segment_id
 * @property int $places_id
 * @property int $partners_id
 * @property int $currency_id
 * @property int $archived
 * @property int $sumTotals
 * @property int $sumCharge
 * @property int $vm_cores
 * @property int $vm_ram
 * @property int $vm_hdd
 * @property float $cost
 * @property float $charge
 * @property string $segmentName
 * @property int[] $depends_ids
 * @property int[] $comps_ids
 * @property int[] $support_ids
 * @property string $supportNames
 * @property int[] $infrastructure_support_ids
 * @property string                 $infrastructureSupportNames
 * @property int[]                  $techs_ids
 * @property int[]      $contracts_ids
 * @property int        $totalUnpaid
 * @property int        $weight
 * @property string     $firstUnpaid
 *
 *
 * @property Comps[]    $comps
 * @property Comps[]    $compsRecursive
 * @property Services[] $depends
 * @property Services[] $dependants
 * @property UserGroups $userGroup
 * @property Techs[]    $techs
 * @property Techs[]    $techsRecursive
 * @property Techs[]    $arms
 * @property Places     $place
 * @property Places[]   $armPlaces
 * @property Places[]   $techPlaces
 * @property Places[]   $phonesPlaces
 * @property Places[]   $inetsPlaces
 * @property Places[]   $places
 * @property Places[]   $sites
 * @property Places[]               $sitesRecursive
 * @property Services               $parentService
 * @property Services               $parent
 * @property Services[] $children
 * @property Schedules $providingSchedule
 * @property Schedules $providingScheduleRecursive
 * @property Users $responsible
 * @property Users $responsibleRecursive
 * @property Users[] $support
 * @property Users[] $supportRecursive
 * @property Users $infrastructureResponsible
 * @property Users $infrastructureResponsibleRecursive
 * @property Users[] $infrastructureSupport
 * @property Users[] $infrastructureSupportRecursive
 * @property Schedules $supportSchedule
 * @property Schedules $supportScheduleRecursive
 * @property Segments $segment
 * @property Segments $segmentRecursive
 * @property Acls[] $acls
 * @property OrgPhones[] $orgPhones
 * @property OrgInet[] $orgInets
 * @property Currency $currency
 * @property Partners $partner
 * @property Contracts $contracts
 * @property Contracts $docs
 * @property Contracts $payments
 * @property MaintenanceReqs $maintenanceReqs
 * @property MaintenanceReqs $maintenanceReqsRecursive
 * @property MaintenanceReqs $backupReqs
 * @property MaintenanceReqs $otherReqs
 */
class Services extends ArmsModel
{
	
	public static $titles='Сервисы/услуги';
	public static $title='Сервис/услуга';

	public static $user_service_title='Сервис для пользователей';
	public static $tech_service_title='Служебный сервис';
	public static $user_job_title='Услуга для пользователей';
	public static $tech_job_title='Услуга';
	
	private $docsCache=null;
	private $segmentRecursiveCache=null;
	private $supportRecursiveCache=null;
	private $responsibleRecursiveCache=null;
	private $infrastructureResponsibleRecursiveCache=null;
	private $infrastructureSupportRecursiveCache=null;
	private $providingScheduleRecursiveCache=null;
	private $supportScheduleRecursiveCache=null;
	private $sitesRecursiveCache=null;
	private $placesCache=null;
	private $sumTotalsCache=null;
	private $sumChargeCache=null;
	private $compsRecursiveCache=null;
	private $techsRecursiveCache=null;
	
	protected static $allItems=null;
	
	
	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'depends_ids' => 'depends',
					'comps_ids' => 'comps',
					'techs_ids' => 'techs',
					'maintenance_reqs_ids' => 'maintenanceReqs',
					'maintenance_jobs_ids' => 'maintenanceJobs',
					'support_ids' => 'support',
					'infrastructure_support_ids' => 'infrastructureSupport',
					'contracts_ids' => 'contracts',
				]
			]
		];
	}

	public function extraFields()
	{
		return [
			'responsibleName',
			'infrastructureResponsibleName',
			'supportScheduleName',
			'providingScheduleName',
			'supportNames',
			'infrastructureSupportNames',
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
			[['cost','charge'], 'number'],
			[['currency_id'],'default','value'=>1],
            [['name', 'description', 'is_end_user'], 'required'],
	        [['depends_ids','comps_ids','support_ids','infrastructure_support_ids','techs_ids','contracts_ids','maintenance_reqs_ids','maintenance_jobs_ids'], 'each', 'rule'=>['integer']],
	        [['description', 'notebook','links'], 'string'],
			[['vm_cores','vm_ram','vm_hdd','places_id','partners_id','places_id','archived','currency_id','weight'],'integer'],
			[['weight'],'default', 'value' => '100'],
	        [['is_end_user', 'is_service', 'responsible_id', 'infrastructure_user_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
			[['name'], 'string', 'max' => 64],
			[['search_text'], 'string', 'max' => 255],
			[['responsible_id'],        'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['responsible_id' => 'id']],
			[['infrastructure_user_id'],'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['infrastructure_user_id' => 'id']],
	        [['providing_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schedules::class, 'targetAttribute' => ['providing_schedule_id' => 'id']],
	        [['support_schedule_id'],   'exist', 'skipOnError' => true, 'targetClass' => Schedules::class, 'targetAttribute' => ['support_schedule_id' => 'id']],
			[['segment_id'],			'exist', 'skipOnError' => true, 'targetClass' => Segments::class, 'targetAttribute' => ['segment_id' => 'id']],
			[['parent_id'],				'exist', 'skipOnError' => true, 'targetClass' => Services::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['parent_id'],				'validateRecursiveLink', 'params'=>['getLink' => 'parentService']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
			'name' => [
				'Название',
				'hint' => 'Короткое уникальное название сервиса или услуги',
				'indexHint' => '{same}<br />'.
					'При поиске также ищет в полях '.
					'<strong>"'.$this->getAttributeLabel('search_text').'"</strong> и '.
					'<strong>"'.$this->getAttributeLabel('description').'"</strong><br />'.
					QueryHelper::$stringSearchHint,
			],
			'parent_id' => [
				'Основной сервис/услуга',
				'hint' => 'Здесь можно указать в состав какого, более крупного сервиса, входит этот сервис',
			],
			'description' => [
				'Описание',
				'hint' => 'Развернутое название или краткое описание назначения этого сервиса. Все детали тут описывать не нужно. Нужно в поле ниже вставить ссылку на вики страничку с описанием',
			],
			'search_text' => [
				'Другие варианты названия',
				'hint' => 'Какие еще названия используются в отношении этого сервиса. Нужно для лучшей работы поиска (искать будет не только по основному названию но и по этим). Через запятую',
			],
	        'links' => [
	        	'Ссылки',
				'hint' => UrlListWidget::$hint.' Нужно обязательно вставить ссылку на вики страничку описания и, если они есть, на странички входа на сервис и поддержки',
			],
			'is_service' => [
				'Тип объекта',
			],
			'is_end_user' => [
				'Предоставляется пользователям',
				'hint' => 'Предоставляется ли этот сервис пользователям (иначе используется другими сервисами)',
			],
	        'depends_ids' => [
	        	'Зависит от сервисов/услуг',
				'hint' => 'От работы каких сервисов зависит работа этого сервиса/предоставление услуги',
	
			],
			'comps_ids' => [
				'Серверы',
				'hint' => 'На каких серверах выполняется этот сервис/услуга',
				'indexHint' => '{same}',//<br />'.QueryHelper::$stringSearchHint,
			],
			'comps' => ['alias'=>'comps_ids',],
			'techs_ids' => [
				'Оборудование',
				'hint' => 'На каком оборудовании выполняется этот сервис',
				'indexHint' => '{same}',//<br />'.QueryHelper::$stringSearchHint,
			],
			'techs' => ['alias'=>'techs_ids'],
	        'providing_schedule_id' => [
	        	'Время предоставления',
				'hint' => 'Расписание, когда сервисом могут воспользоваться пользователи или другие сервисы',
			],
			'providingSchedule' => ['alias'=>'providing_schedule_id'],
	        'support_schedule_id' => [
	        	'Время поддержки',
				'hint' => 'Расписание, когда нужно реагировать на сбои в работе сервиса',
			],
			'supportSchedule' => ['alias'=>'support_schedule_id'],
			'responsible_id' => [
				'Ответственный',
				'hint' => 'Ответственный за работу сервиса/оказание услуги',
			],
			'infrastructure_user_id' => [
				'Ответственный за инфраструктуру',
				'hint' => 'Ответственный за инфраструктуру сервиса (если отличается от ответственного за сервис)',
			],
			'infrastructure_support_ids' => [
				'Поддержка инфраструктуры',
				'hint' => 'Дополнительные члены команды по поддержке инфраструктуры сервиса<br />'.
					'(если отличаются от поддержки сервиса)',
			],
			'responsible' => [
				'Ответственный, поддержка',
				'indexLabel'=>'Отв., поддержка',
				'indexHint'=>'Поиск по ответственному или поддержке сервиса или инфраструктуры сервиса.<br />'.QueryHelper::$stringSearchHint,
			],
			'support_ids' => [
				'Поддержка',
				'hint' => 'Дополнительные члены команды по поддержке сервиса/оказанию услуги',
			],
			'contracts_ids' => [
				Contracts::$titles,
				'hint' => 'Привязанные к услуге документы. Нужно привязать только договор, а все счета/акты/доп.соглашения уже привязывать к договору',
			],
			'segment_id' => [
				'Сегмент ИТ',
				'hint' => 'Сегмент ИТ инфраструктуры к которому относится этот сервис',
			],
			'segment' => ['alias'=>'segment_id'],
			'arms' => [Techs::$title],
			'archived' => [
				'Архивирован',
				'hint' => 'Если сервис/услуга более не используется, но для истории его описание лучше сохранить - то его можно просто заархивировать, чтобы не отсвечивал',
			],
			'places_id' => [
				Places::$title,
				'hint' => 'Привязать сервис/услугу к помещению. Иначе помещение будет косвенно выясняться на основании расположения серверов и оборудования',
			],
			'places' => ['alias'=>'places_id'],
			'partners_id' => [
				Partners::$title,
				'hint' => 'Если услуга/сервис оказывается каким-либо контрагентом (иначе внутренняя)',
			],
			'partner' => ['alias'=>'partners_id'],
			'sites' => ['Площадки'],
			'currency_id' => [
				'Валюта',
				'hint' => 'Ед. изм. стоим.',
			],
			'cost' => [
				'Стоимость',
				'hint' => 'Стоимость услуги в месяц. (если понадобится другой период - обращайтесь к разработчику)',
			],
			'charge' => [
				'НДС',
				'hint' => 'налог',
			],
			'notebook' => [
				'Записная книжка',
				'hint' => 'Можно сохранять тут подробное описание сервиса',
			],
			'weight' => [
				'Вес',
				'hint' => 'Значимость сервиса по сравнению с другими. Используется для <ul>'
					.'<li> определения наиболее весомых сервисов на сервере для выбора ответственного за сервер</li>'
					.'<li> распределения ресурсов VM между сервисами на ней</li>'
					.'</ul>'
			],
			'maintenance_reqs_ids'=>[
				MaintenanceReqs::$titles,
				'hint'=>'Какие требования предъявляет сервис по резервному копированию,'
					.'<br>переиндексации, обновлению, перезагрузкам и т.п.',
				'indexLabel'=>'Обслуживание',
				'indexHint'=>'{same}'
			],
			'maintenanceReqs'=>['alias'=>'maintenance_reqs_ids'],
			'maintenanceReqsRecursive'=>['alias'=>'maintenance_reqs_ids'],
			'backupReqs'=>[
				'Требования по резервному копированию',
				'indexHint'=>'Какие требования по бэкапам предъявляет сервис',
				'indexLabel'=>'Рез. треб.',
			],
			'otherReqs'=>[
				'Требования по обслуживанию',
				'indexHint'=>'Какие требования по регламентному обслуживанию предъявляет сервис',
				'indexLabel'=>'Обсл. треб.',
			],
			'vm_cores' => [
				'Выделено VM CPU',
				'hint' => 'Количество ядер VM резервированное/запланированное для этого сервиса.<br>'.
					'(Если на этот сервис запланированы ресурсы виртуализации)'
			],
			'vm_ram' => [
				'Выделено VM RAM',
				'hint' => 'Объем VM RAM в GiB зарезервированный/запланированный для этого сервиса.<br>'.
					'(Если на этот сервис запланированы ресурсы виртуализации)'
			],
			'vm_hdd' => [
				'Выделено VM HDD',
				'hint' =>'Объем дискового пространства VM в GiB зарезервированный/запланированный для этого сервиса.<br>'.
					'(Если на этот сервис запланированы ресурсы виртуализации)'
			],
			'maintenance_jobs_ids'=>[
				MaintenanceJobs::$titles,
				'hint'=>'Какие операции регламентного обслуживания проводятся над этим сервисом',
				'indexHint'=>'{same}'
			],
			'maintenanceJobs'=>['alias'=>'maintenance_jobs_ids'],
        ];
    }
    
	
	public function reverseLinks()
	{
		return [
			$this->children,
			$this->depends,
			$this->dependants,
			$this->contracts,
			$this->comps,
			$this->techs,
			$this->acls,
			$this->orgInets,
			$this->orgPhones
		];
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::class, ['id' => 'currency_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::class, ['id' => 'partners_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getSupportSchedule()
	{
		return $this->hasOne(Schedules::class, ['id' => 'support_schedule_id'])
			->from(['support_schedule'=>Schedules::tableName()]);
	}
	
	public function getSupportScheduleRecursive() {
		if (is_object($this->supportScheduleRecursiveCache)) return $this->supportScheduleRecursiveCache;
		if (is_object($this->supportScheduleRecursiveCache = $this->supportSchedule))
			return $this->supportScheduleRecursiveCache;
		if (is_object($this->parentService))
			return $this->supportScheduleRecursiveCache=$this->parentService->supportScheduleRecursive;
		return null;
	}
	
	public function getSupportScheduleName() {
		if (is_object($this->supportScheduleRecursive)) return $this->supportScheduleRecursive->name;
		return null;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getProvidingSchedule()
	{
		return $this->hasOne(Schedules::class, ['id' => 'providing_schedule_id'])
			->from(['providing_schedule'=>Schedules::tableName()]);
	}
	
	/**
	 * @return Schedules|null
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getProvidingScheduleRecursive() {
		if (is_object($this->providingScheduleRecursiveCache)) return $this->providingScheduleRecursiveCache;
		if (is_object($this->providingScheduleRecursiveCache = $this->providingSchedule))
			return $this->providingScheduleRecursiveCache;
		if (is_object($this->parentService))
			return $this->providingScheduleRecursiveCache = $this->parentService->providingScheduleRecursive;
		return null;
	}
	
	public function getProvidingScheduleName() {
		if (is_object($this->providingScheduleRecursive)) return $this->providingScheduleRecursive->name;
		return null;
	}
	
	/**
	 * @return Segments|ActiveQuery
	 */
	public function getParentService()
	{
		if (!$this->parent_id) return null;
		if ($this->parent_id == $this->id) return null;
		if (static::allItemsLoaded()) return static::getLoadedItem($this->parent_id);
		return $this->hasOne(Services::class, ['id' => 'parent_id']);
	}
	
	/**
	 * Непосредственные потомки
	 * @return Services[]|ActiveQuery
	 */
	public function getChildren()
	{
		if (static::allItemsLoaded()) return ArrayHelper::findByField(
			static::getAllItems(),
			'parent_id',
			$this->id
		);
		return $this->hasMany(Services::class, ['parent_id' => 'id'])
			->from(['service_children'=>self::tableName()]);
	}
	
	/**
	 * Все потомки (включая потомков потомков)
	 * @return Services[]|ActiveQuery
	 */
	public function getChildrenRecursive()
	{
		if (static::allItemsLoaded()) {
			$items=$this->getChildren();
			$result=$items;
			foreach ($items as $item) {
				$result=array_merge($result,$item->getChildrenRecursive());
			}
			return $result;
		}
		return $this->hasMany(Services::class, ['parent_id' => 'id'])
			->from(['service_children'=>self::tableName()]);
	}
	
	
	/**
	 * @return ActiveQuery
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getInfrastructureResponsible()
	{
		return $this->hasOne(Users::class, ['id' => 'infrastructure_user_id'])
			->from(['infrastructure_responsible'=>Users::tableName()]);
	}
	
	public function getInfrastructureResponsibleRecursive() {
		if (is_object($this->infrastructureResponsibleRecursiveCache))
			return $this->infrastructureResponsibleRecursiveCache;
		
		if (is_object($this->infrastructureResponsibleRecursiveCache = $this->infrastructureResponsible))
			return $this->infrastructureResponsibleRecursiveCache;
		
		if (is_object($this->parentService)) {
			return $this->infrastructureResponsibleRecursiveCache = $this
				->parentService
				->infrastructureResponsibleRecursive;
		}
		return null;
	}
	
	/**
	 * @return string
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getInfrastructureResponsibleName() {
		if (is_object($this->infrastructureResponsibleRecursive)) return $this->infrastructureResponsibleRecursive->Ename;
		//если никто не нашелся за инфраструктуру, тогда за нее отвечает ответственный за сервис
		return $this->responsibleName;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getResponsible()
	{
		return $this->hasOne(Users::class, ['id' => 'responsible_id'])
			->from(['responsible'=>Users::tableName()]);
	}
	
	public function getResponsibleRecursive() {
		if (is_object($this->responsibleRecursiveCache)) return $this->responsibleRecursiveCache;
		if (is_object($this->responsibleRecursiveCache = $this->responsible))
			return $this->responsibleRecursiveCache;
		if (is_object($this->parentService)) {
			return $this->responsibleRecursiveCache = $this->parentService->responsibleRecursive;
		}
		return null;
	}

	
	public function getResponsibleName() {
		if (is_object($this->responsibleRecursive)) return $this->responsibleRecursive->Ename;
		return null;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::class, ['id' => 'segment_id'])
			->from(['services_segment'=>Segments::tableName()]);
	}
	
	/**
	 * Возвращает сегмент сервиса с учетом родителей
	 * @return string|null
	 */
	public function getSegmentRecursive() {
		if (is_object($this->segmentRecursiveCache)) return $this->segmentRecursiveCache;
		if (is_object($this->segmentRecursiveCache = $this->segment))
			return $this->segmentRecursiveCache;
		if (is_object($this->parentService))
			return $this->segmentRecursiveCache = $this->parentService->segment;
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
		return $this->hasMany(Services::class, ['id' => 'depends_id'])
			->from(['service_depend'=>Services::tableName()])
			->viaTable('{{%services_depends}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает команду техподдержки
	 */
	public function getSupport()
	{
		return $this->hasMany(Users::class, ['id' => 'user_id'])
			->from(['support'=>Users::tableName()])
			->viaTable('{{%users_in_services}}', ['service_id' => 'id']);
	}
	
	public function getSupportRecursive() {
		if (is_array($this->supportRecursiveCache) && count($this->supportRecursiveCache)) {
			return $this->supportRecursiveCache;
		}
		if (is_array($this->supportRecursiveCache = $this->support) && count($this->supportRecursiveCache)){
			//var_dump($this->supportRecursiveCache);
			return $this->supportRecursiveCache;
			
		}
		if (is_object($this->parentService))
			return $this->supportRecursiveCache = $this->parentService->supportRecursive;
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
	 * Возвращает команду поддержки инфраструктуры
	 */
	public function getInfrastructureSupport()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->from(['infrastructure_support'=>Users::tableName()])
			->viaTable('{{%users_in_svc_infrastructure}}', ['services_id' => 'id']);
	}
	
	public function getInfrastructureSupportRecursive() {
		if (is_array($this->infrastructureSupportRecursiveCache) && count($this->infrastructureSupportRecursiveCache)) {
			return $this->infrastructureSupportRecursiveCache;
		}
		if (is_array($this->infrastructureSupportRecursiveCache = $this->infrastructureSupport) && count($this->infrastructureSupportRecursiveCache)){
			//var_dump($this->supportRecursiveCache);
			return $this->infrastructureSupportRecursiveCache;
			
		}
		if (is_object($this->parentService))
			return $this->infrastructureSupportRecursiveCache = $this->parentService->infrastructureSupportRecursive;
		return [];
	}
	
	public function getInfrastructureSupportNames() {
		$names=[];
		foreach ($this->infrastructureSupportRecursive as $user)
			if (is_object($user)) $names[]=$user->Ename;
			
		if (count($names)) return implode(',',$names);
		//если никто не нашелся за инфраструктуру, тогда за нее отвечает ответственный за сервис
		return $this->supportNames;
	}
	
	/**
	 * Возвращает документы привязанные к сервису
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->from(['service_contracts'=>Contracts::tableName()])
			->viaTable('{{%contracts_in_services}}', ['services_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы зависимые от этого сервиса
	 */
	public function getDependants()
	{
		return $this->hasMany(Services::class, ['id' => 'service_id'])
			->from(['dependant_services'=>Services::tableName()])
			->viaTable('{{%services_depends}}', ['depends_id' => 'id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::class, ['id' => 'tech_id'])
			->viaTable('{{%techs_in_services}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает группу ответственных за сервис
	 */
	public function getUserGroup()
	{
		return $this->hasOne(UserGroups::class, ['id' => 'user_group_id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('comps_in_services', ['services_id' => 'id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис (и дочерние)
	 */
	public function getCompsRecursive()
	{
		if (is_null($this->compsRecursiveCache)) {
			$this->compsRecursiveCache=[];
			foreach ($this->comps as $comp)
				$this->compsRecursiveCache[$comp->id]=$comp;
			
			foreach ($this->children as $child)
				$this->compsRecursiveCache=ArrayHelper::recursiveOverride($child->compsRecursive,$this->compsRecursiveCache);
		}
		return $this->compsRecursiveCache;
	}
	
	/**
	 * Возвращает оборудование на котором живет этот сервис (и дочерние)
	 */
	public function getTechsRecursive()
	{
		if (is_null($this->techsRecursiveCache)) {
			$this->techsRecursiveCache=[];
			foreach ($this->techs as $tech)
				$this->techsRecursiveCache[$tech->id]=$tech;
			
			foreach ($this->children as $child)
				$this->techsRecursiveCache=ArrayHelper::recursiveOverride($child->techsRecursive,$this->techsRecursiveCache);
		}
		return $this->techsRecursiveCache;
	}
	
	public function getArms()
	{
		return $this->hasMany(Techs::class, ['id' => 'arm_id'])
			->via('comps');
	}
	
	public function getMaintenanceReqs()
	{
		return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])
			->viaTable('maintenance_reqs_in_services', ['services_id' => 'id']);
	}

	public function getMaintenanceJobs()
	{
		return $this->hasMany(MaintenanceJobs::class, ['id' => 'jobs_id'])
			->viaTable('maintenance_jobs_in_services', ['services_id' => 'id']);
	}
	
	public function getMaintenanceReqsRecursive()
	{
		return $this->findRecursiveAttr('maintenanceReqs','maintenanceReqsRecursive','parentService', []);
	}
	
	public function getBackupReqs()
	{
		return ArrayHelper::getItemsByFields($this->maintenanceReqsRecursive??[],['is_backup'=>1]);
	}
	
	public function getOtherReqs()
	{
		return ArrayHelper::getItemsByFields($this->maintenanceReqsRecursive??[],['is_backup'=>0]);
	}
	
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}
	
	public function getArmPlaces()
	{
		return $this->hasMany(Places::class, ['id' => 'places_id'])
			->from(['places_in_svc_arms'=>Places::tableName()])
			->via('arms');
	}
	
	public function getTechPlaces()
	{
		return $this->hasMany(Places::class, ['id' => 'places_id'])
			->from(['places_in_svc_techs'=>Places::tableName()])
			->via('techs');
	}
	
	public function getInetsPlaces()
	{
		return $this->hasMany(Places::class, ['id' => 'places_id'])
			->from(['places_in_svc_inets'=>Places::tableName()])
			->via('orgInets');
	}
	
	public function getPhonesPlaces()
	{
		return $this->hasMany(Places::class, ['id' => 'places_id'])
			->from(['places_in_svc_phones'=>Places::tableName()])
			->via('orgPhones');
	}
	
	public function getPlaces() {
		if (is_null($this->placesCache)) {
			if (Places::allItemsLoaded()) {
				$this->placesCache=[$this->places_id=>Places::getLoadedItem($this->places_id)];
				foreach ($this->techs as $item)
					if (!isset($this->placesCache[$item->places_id]))
						$this->placesCache[$item->places_id]=Places::getLoadedItem($item->places_id);
				foreach ($this->arms as $item)
					if (!isset($this->placesCache[$item->places_id]))
						$this->placesCache[$item->places_id]=Places::getLoadedItem($item->places_id);
				foreach ($this->orgPhones as $item)
					if (!isset($this->placesCache[$item->places_id]))
						$this->placesCache[$item->places_id]=Places::getLoadedItem($item->places_id);
				foreach ($this->orgInets as $item)
					if (!isset($this->placesCache[$item->places_id]))
						$this->placesCache[$item->places_id]=Places::getLoadedItem($item->places_id);
			} else {
				$this->placesCache=[$this->places_id=>$this->place];
				foreach (array_merge($this->techPlaces,$this->armPlaces,$this->phonesPlaces,$this->inetsPlaces) as $place)
					$this->placesCache[$place->id]=$place;
			}
		}
		return $this->placesCache;
	}
	
	public function getSites(){
		$sites=[];
		foreach ($this->places as $place) {
			if (is_object($place)) {
				$site=$place->top;
				$sites[$site->id]=$site;
			}
		}
		return $sites;
	}

	public function getSitesRecursive(){
		if (is_array($this->sitesRecursiveCache)) return $this->sitesRecursiveCache;
		$sites=[];
		foreach ($this->sites as $site)
			$sites[$site->id]=$site;
		foreach ($this->children as $service)
			foreach ($service->sitesRecursive as $site)
				$sites[$site->id]=$site;
		
		return $this->sitesRecursiveCache = $sites;
	}
	
	public function getSumTotals()
	{
		if ($this->cost) return $this->cost;
		if (!is_null($this->sumChargeCache)) return $this->sumChargeCache;
		$this->sumChargeCache=0;
		foreach ($this->orgPhones as $phone)	$this->sumChargeCache+=$phone->cost;
		foreach ($this->orgInets as $inet)		$this->sumChargeCache+=$inet->cost;
		foreach ($this->children as $service)	$this->sumChargeCache+=$service->sumTotals;
		return $this->sumChargeCache;
	}
	
	public function getSumCharge()
	{
		if ($this->charge) return $this->charge;
		if (!is_null($this->sumTotalsCache)) return $this->sumTotalsCache;
		$this->sumTotalsCache=0;
		foreach ($this->orgPhones as $phone)	$this->sumTotalsCache+=$phone->charge;
		foreach ($this->orgInets as $inet)		$this->sumTotalsCache+=$inet->charge;
		foreach ($this->children as $service)	$this->sumTotalsCache+=$service->sumCharge;
		return $this->sumTotalsCache;
	}
	
	/**
	 * Проверяет что этот сервис косвенно входит в сервис с ID serviceID
	 * @param $serviceID
	 * @return bool
	 */
	public function inService($serviceID) {
		if ($this->id === $serviceID) return true;
		if (is_object($this->parentService)) return $this->parentService->inService($serviceID);
		return false;
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['services_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getOrgInets()
	{
		return $this->hasMany(OrgInet::class, ['services_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getOrgPhones()
	{
		return $this->hasMany(OrgPhones::class, ['services_id' => 'id']);
	}
	
	/**
	 * Список привязанных к сервису документов
	 * @return Contracts[]
	 */
	public function getDocs()
	{
		if (!is_null($this->docsCache)) return $this->docsCache;
		
		$this->docsCache=[];
		foreach ($this->contracts as $contract) {
			$this->docsCache[]=$contract;
			/**
			 * @var $contract Contracts
			 */
			foreach ($contract->allChildren as $child) {
				$this->docsCache[]=$child;
			}
		}
		return $this->docsCache;
	}
	
	/**
	 * Список платежных документов
	 * @return Contracts[]
	 */
	public function getPayments()
	{
		$payments=[];
		foreach ($this->docs as $doc) {
			/**
			 * @var $doc Contracts
			 */
			if ($doc->total) $payments[]=$doc;
		}
		return $payments;
	}
	
	
	/**
	 * Сумма неоплаченных документов
	 * @return array
	 */
	public function getTotalUnpaid()
	{
		$total=[];
		foreach ($this->payments as $doc)
			/**
			 * @var $doc Contracts
			 */
			if ($doc->isUnpaid) {
				$currency=$doc->currency->symbol;
				if (!isset($total[$currency])) $total[$currency]=0;
				$total[$currency]+=$doc->total;
			}
			
		return $total;
	}
	
	/**
	 * Сумма неоплаченных документов
	 * @return int
	 */
	public function getFirstUnpaid()
	{
		$iFirst=0;
		$strFirst=null;
		foreach ($this->payments as $doc)
			/** @var $doc Contracts */
			if ($doc->isUnpaid) {
				if (!$iFirst || strtotime($doc->date)<$iFirst) {
					$strFirst=$doc->date;
					$iFirst=strtotime($strFirst);
				}
			}
	
		return $strFirst;
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
	
	/**
	 * @return array
	 */
	public static function fetchProviderNames(){
		$list= static::find()
			->select(['id','name'])
			->where(['is_service'=>0])
			->orderBy('name')
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
	public static function cacheAllItems() {
		if (!static::allItemsLoaded())
			static::$allItems=ArrayHelper::index(
				static::find()
				->with([
					'comps',
					'arms',
					'acls',
					'segment',
					'orgPhones',
					'children',
					'depends',
					'place',
					'comps',
					'arms.state',
					'techs.state',
					'arms.place',
					'techs.place',
					'responsible',
					'support',
					'providingSchedule',
					'supportSchedule',
					'orgPhones',
					'orgInets',
					'orgPhones.place',
					'orgInets.place',
					'contracts',
					'maintenanceReqs'
					])
				->all()
			,'id');
	}
	
	/**
	 * Возвращает ответственного исходя из пачки сервисов на узле
	 * @param $services
	 * @return Users|mixed|null
	 */
	public static function responsibleFrom($services) {
		if (is_array($services) && count($services)) {
			$persons=[];
			$rating=[];
			/** @var $service Services */
			foreach ($services as $service) if(!$service->archived) {
				
				$responsible=null;
				//сначала проверяем ответственного за инфраструктуру
				if (is_object($service->infrastructureResponsibleRecursive)) {
					$responsible=$service->infrastructureResponsibleRecursive;
					//уже потом за сам сервис
				} elseif (is_object($service->responsibleRecursive)) {
					$responsible=$service->responsibleRecursive;
				}
				
				if (is_object($responsible)) {
					$responsible_id=$responsible->id;
					if (!isset($rating[$responsible_id])) {
						$rating[$responsible_id]=$service->weight;
						$persons[$responsible_id]=$responsible;
					} else
						$rating[$responsible_id]+=$service->weight;
				}
			}
			if (count($rating)) return $persons[array_search(max($rating), $rating)];
		}
		return null;
	}
	
	public static function supportTeamFrom($services) {
		$team=[];
		if (is_array($services) && count($services)) {
			/** @var $service Services */
			foreach ($services as $service) if(!$service->archived) {
				
				$responsible=null;
				//сначала проверяем ответственного за инфраструктуру
				if (is_object($service->infrastructureResponsibleRecursive)) {
					$responsible=$service->infrastructureResponsibleRecursive;
					//уже потом за сам сервис
				} elseif (is_object($service->responsibleRecursive)) {
					$responsible=$service->responsibleRecursive;
				}
				//ответственные за сервисы на машине
				if (is_object($responsible)) $team[$responsible->id]=$responsible;
				
				
				$support=[];
				//сначала проверяем ответственного за инфраструктуру
				if (count($service->infrastructureSupportRecursive)) {
					$support=$service->infrastructureSupportRecursive;
					//уже потом за сам сервис
				} elseif (count($service->supportRecursive)) {
					$support=$service->supportRecursive;
				}
				//поддержка сервисов на машине
				if (count($support)) {
					foreach ($support as $item) {
						if (is_object($item))
							$team[$item->id]=$item;
					}
				}
			}
		}
		return $team;
	}
	
	/**
	 * Моделирует поддержку сервиса (смотрит кто его поддерживает и моделирует отсутствие сотрудников)
	 * @param $dismissed array кого нет
	 * @return array
	 */
	public function supportModeling(array $dismissed) {
		$responsible=$this->responsibleRecursive;
		if (is_object($responsible)) {
			$responsible_id=$this->responsibleRecursive->id;
			$support_ids[$responsible_id]=$responsible_id;
		} else $support_ids=[];
		
		foreach ($this->supportRecursive as $user) if (is_object($user)) {
			$user_id=$user->id;
			$support_ids[$user_id]=$user_id;
		}
		return array_diff($support_ids,$dismissed);
	}
	
}
