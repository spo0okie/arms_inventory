<?php

namespace app\models;

use app\components\UrlListWidget;
use app\helpers\ArrayHelper;
use app\helpers\QueryHelper;
use app\helpers\StringHelper;
use app\models\traits\AclsFieldTrait;
use app\models\traits\ServicesModelCalcFieldsTrait;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "services".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property string $description
 * @property string $search_text
 * @property int $is_end_user
 * @property int $user_group_id
 * @property int $sla_id
 * @property int $is_service
 * @property string $notebook
 * @property string $links
 * @property string $linksRecursive
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
 * @property Comps[]    $provideComps
 * @property Comps[]    $comps
 * @property Comps[]    $compsRecursive
 * @property Techs[]    $techs
 * @property Techs[]    $techsRecursive
 * @property Techs[]    $nodes
 * @property Techs[]    $nodesRecursive
 * @property Services[] $depends
 * @property Services[] $dependants
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
 * @property MaintenanceJobs $maintenanceJobs

 */
class Services extends ArmsModel
{
	use ServicesModelCalcFieldsTrait,AclsFieldTrait;
	
	public $treeChildren=null;
	public $treeDepth=null;
	public $treePrefix=null;
	
	public static $titles='Сервисы/услуги';
	public static $title='Сервис/услуга';

	public static $user_service_title='Сервис для пользователей';
	public static $tech_service_title='Служебный сервис';
	public static $user_job_title='Услуга для пользователей';
	public static $tech_job_title='Услуга';
	
	public static $job_title='Услуга';
	public static $service_title='Сервис';
	
	
	public $parentAttr='parentService';
	private $sitesRecursiveCache=null;
	private $placesCache=null;
	
	protected static $allItems=null;
	
	public $linksSchema=[
		'depends_ids' =>				[Services::class,'dependants_ids'],
		'comps_ids' =>					[Comps::class,'services_ids'],
		'provide_comps_ids' =>			[Comps::class,'platform_id'],
		'techs_ids' =>					[Techs::class,'services_ids'],
		'managed_techs_ids' =>			[Techs::class,'management_service_id'],
		'maintenance_reqs_ids'=>		[MaintenanceReqs::class,'services_ids'],
		'maintenance_jobs_ids'=>		[MaintenanceJobs::class,'services_ids'],
		'support_ids' =>				[Users::class,'support_services_ids','loader'=>'support'],
		'infrastructure_support_ids' =>	[Users::class,'infrastructure_support_services_ids','loader'=>'infrastructureSupport'],
		'contracts_ids' => 				[Contracts::class,'services_ids'],
		'acls_ids' => 					[Acls::class,'services_id'],
		'aces_ids' => 					[Aces::class,'services_ids'],
		'children_ids' =>				[Services::class,'parent_id','loader'=>'children'],
		'org_inets_ids'=>				[OrgInet::class,'services_id'],
		'org_phones_ids'=>				[OrgPhones::class,'services_id'],
		
		'responsible_id' =>				[Users::class,'services_ids'],
		'infrastructure_user_id' =>		[Users::class,'infrastructure_services_ids','loader'=>'infrastructureResponsible'],
		'providing_schedule_id' =>		[Schedules::class,'providing_services_ids'],
		'support_schedule_id' =>		[Schedules::class,'support_services_ids'],
		'segment_id' =>					[Segments::class,'services_ids'],
		'parent_id' =>					[Services::class,'children_ids','loader'=>'parentService'],
		'partners_id' =>				[Partners::class,'services_ids'],
		'places_id' =>					[Places::class,'services_ids'],
		'currency_id' =>				Currency::class,
	];
	

	public function extraFields()
	{
		return [
			'responsibleName',
			'supportNames',
			'responsibleRecursive',
			'supportRecursive',
			'infrastructureResponsibleName',
			'infrastructureSupportNames',
			'infrastructureResponsibleRecursive',
			'infrastructureSupportRecursive',
			'supportScheduleName',
			'providingScheduleName',
			'segmentName',
			'segmentRecursive',
			'nameWithoutParent',
			'comps',
			'techs'
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
			'archived' => [
				'Архивирован',
				'hint' => 'Если сервис/услуга более не используется, но для истории его описание лучше сохранить - то его можно просто заархивировать, чтобы не отсвечивал',
			],
			'aces_ids' => [
				'Доступы отсюда',
				'hint' => 'Исходящие доступы от этого сервиса/услуги к другим сервисам/услугам',
			],
			'acls_ids' => [
				'Доступы сюда',
				'hint' => 'Входящие доступы к этому сервису/услуге от других сервисов/услуг',
			],
			'arms' => [
				'Физ. сервера',
				'indexHint'=>'АРМ/оборудование, на которых крутятся ОС/ВМ, на которых крутится сервис'
					.'<br>(Не то же самое, что оборудование на котом крутится сервис.)'
					.'<br>Это не прямая, а косвенная связь. При миграции ВМ это список может меняться'
			],
			'backupReqs'=>[
				'Требования по резервному копированию',
				'indexHint'=>'Какие требования по бэкапам предъявляет сервис',
				'indexLabel'=>'Рез. треб.',
			],
			'charge' => [
				'НДС',
				'hint' => 'налог',
			],
			'comps_ids' => [
				'Серверы',
				'hint' => 'На каких серверах выполняется этот сервис/услуга',
				'indexHint' => '{same}',//<br />'.QueryHelper::$stringSearchHint,
				'placeholder' => 'Выберите серверы',
			],
			'compsAndTechs'=> [
				'Серв./Оборуд.',
				'indexHint' => 'Серверы и оборудование на которых выполняется этот сервис'
			],
			'contracts_ids' => [
				Contracts::$titles,
				'hint' => 'Привязанные к услуге документы. Нужно привязать только договор, а все счета/акты/доп.соглашения уже привязывать к договору',
				'placeholder' => 'Нет связанных документов',
			],
			'cost' => [
				'Стоимость',
				'hint' => 'Стоимость услуги в месяц. (если понадобится другой период - обращайтесь к разработчику)',
			],
			'currency_id' => [
				'Валюта',
				'hint' => 'Ед. изм. стоим.',
				'placeholder' => 'RUR'
			],
			'depends_ids' => [
				'Зависит от сервисов/услуг',
				'hint' => 'От работы каких сервисов зависит работа этого сервиса/предоставление услуги',
				'placeholder' => 'Не зависит ни от каких сервисов',
			],
			'description' => [
				'Краткое описание',
				'indexLabel' => 'Описание',
				'hint' => 'Развернутое название или краткое описание назначения этого сервиса.<br>'
					.'Все детали тут описывать не нужно. Их нужно описать в поле "Подробно" или <br>'
					.'в поле ниже вставить ссылку на вики страничку с детальным описанием.',
				'type' => 'text',
			],
			'infrastructure_user_id' => [
				'Ответственный за инфраструктуру',
				'hint' => 'Ответственный за инфраструктуру сервиса (если отличается от ответственного за сервис)',
				'is_inheritable'=>true,
				'placeholder' => function() {return is_object($this->responsibleRecursive)?
					$this->responsibleRecursive->name.' (отв. за сервис, включая инфраструктуру)':
					'Тот же, кто отвечает за сервис (ответственность не разделяется)';
				},
			],
			'infrastructure_support_ids' => [
				'Поддержка инфраструктуры',
				'hint' => 'Дополнительные члены команды по поддержке инфраструктуры сервиса<br />'.
					'(если отличаются от поддержки сервиса)',
				'is_inheritable'=>true,
				'placeholder' => function() {return count($this->getSupportRecursive())?
					$this->renderAttributeToText('support_ids',', ').' (поддерживают сервис, включая инфраструктуру)':
					'Те же, кто поддерживает сервис (ответственность не разделяется)';
				}
			],
			'is_end_user' => [
				'Предоставляется пользователям',
				'hint' => 'Предоставляется ли этот сервис пользователям (иначе используется другими сервисами)',
			],
			'is_service' => [
				'Тип объекта',
			],
			'links' => [
				'Ссылки',
				'hint' => UrlListWidget::$hint.' Нужно обязательно вставить ссылку на вики страничку описания и, если они есть, на странички входа на сервис и поддержки',
			],
			'maintenance_jobs_ids'=>[
				MaintenanceJobs::$titles,
				'hint'=>'Какие операции регламентного обслуживания проводятся над этим сервисом',
				'indexHint'=>'{same}',
				'placeholder' => 'Не обслуживается',
			],
			'maintenance_reqs_ids'=>[
				MaintenanceReqs::$titles,
				'hint'=>'Какие требования предъявляет сервис по резервному копированию,'
					.'<br>переиндексации, обновлению, перезагрузкам и т.п.',
				'indexLabel'=>'Треб. обслуживание',
				'indexHint'=>'{same}',
				'is_inheritable'=>true,
				'placeholder' => 'Не требует обслуживания',
			],
			'maintenanceReqsRecursive'=>['alias'=>'maintenance_reqs_ids'],
			'name' => [
				'Название',
				'hint' => 'Короткое уникальное название сервиса или услуги',
				'indexHint' => '{same}<br />'.
					'При поиске также ищет в полях '.
					'<strong>"Другие варианты названия"</strong> и '.
					'<strong>"Описание"</strong><br />'.
					QueryHelper::$stringSearchHint,
			],
			'notebook' => [
				'Подробно',
				'hint' => 'Можно сохранять тут подробное описание сервиса',
				'type' => 'text'
			],
			'otherReqs'=>[
				'Требования по обслуживанию',
				'indexHint'=>'Какие требования по регламентному обслуживанию предъявляет сервис',
				'indexLabel'=>'Обсл. треб.',
			],
			'parent_id' => [
				'Основной сервис/услуга',
				'hint' => 'Здесь можно указать в состав какого, более крупного сервиса, входит этот сервис',
				'placeholder' => 'Выберите основной сервис/услугу',
			],
			'partners_id' => [
				Partners::$title,
				'hint' => 'Если услуга/сервис оказывается каким-либо контрагентом (иначе внутренняя)',
				'placeholder' => 'Отсутствует: сервис/услуга предоставляется ИТ отделом'
			],
			'partner' => ['alias'=>'partners_id'],
			'places_id' => [
				Places::$title,
				'hint' => 'Привязать сервис/услугу к помещению. Иначе помещение будет косвенно выясняться на основании расположения серверов и оборудования',
				'placeholder' => 'Определять автоматически из расположения серверов и оборудования',
			],
			'providing_schedule_id' => [
	        	'Время предоставления',
				'hint' => 'Расписание, когда сервисом могут воспользоваться пользователи или другие сервисы',
				'is_inheritable'=>true,
				'placeholder' => 'Расписание отсутствует'
			],
			'responsible_id' => [
				'Ответственный',
				'hint' => 'Ответственный за работу сервиса/оказание услуги',
				'is_inheritable'=>true,
				'placeholder' => 'Ответственный не назначен',
				'indexLabel'=>'Отв., поддержка',
				'indexHint'=>'Поиск по ответственному или поддержке сервиса или инфраструктуры сервиса.<br />'.QueryHelper::$stringSearchHint,
			],
			'segment_id' => [
				'Сегмент ИТ',
				'hint' => 'Сегмент ИТ инфраструктуры к которому относится этот сервис',
				'is_inheritable'=>true,
				'placeholder' => 'Сегмент инфраструктуры не объявлен'
			],
			'search_text' => [
				'Альясы',
				'hint' => 'Какие еще названия используются в отношении этого сервиса (по одному в строку). '
					.'<br>Нужно для лучшей работы поиска (искать будет не только по основному названию но и по этим).'
					.'<br>Также при построении дерева сервисов имена и альясы родителей скрывается из дочерних сервисов (для краткости)',
			],
			'sites' => ['Площадки'],
			'support_ids' => [
				'Поддержка',
				'hint' => 'Дополнительные члены команды по поддержке сервиса/оказанию услуги',
				'is_inheritable'=>true,
				'placeholder' => 'Поддержка отсутствует'
			],
	        'support_schedule_id' => [
	        	'Время поддержки',
				'hint' => 'Расписание, когда нужно реагировать на сбои в работе сервиса',
				'is_inheritable'=>true,
				'placeholder' => 'Расписание отсутствует'
			],
			'techs_ids' => [
				'Оборудование',
				'hint' => 'На каком оборудовании выполняется этот сервис',
				'indexHint' => '{same}',//<br />'.QueryHelper::$stringSearchHint,
				'placeholder' => 'Выберите оборудование',
			],
			'weight' => [
				'Вес',
				'hint' => 'Значимость сервиса по сравнению с другими. Используется для <ul>'
					.'<li> определения наиболее весомых сервисов на сервере для выбора ответственного за сервер</li>'
					.'<li> распределения ресурсов VM между сервисами на ней</li>'
					.'</ul>'
					.(
						(\Yii::$app->params['support.service.min.weight']??0)?
						(
							'Команды обслуживания сервисов весом менее <b>'.Yii::$app->params['support.service.min.weight'].'</b> не будут привлекаться к обслуживанию ОС и оборудования на которых развернут сервис'
						):''
					)
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

        ];
    }
    
	
	/*public function reverseLinks()
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
	}*/
	
	/**
	 * Возвращает все варианты написания/названия сервиса
	 */
	public function getAliases()
	{
		return array_merge([$this->name],StringHelper::explode($this->search_text,"\n",true,true));
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
	
	/**
	 * @return ActiveQuery
	 */
	public function getProvidingSchedule()
	{
		return $this->hasOne(Schedules::class, ['id' => 'providing_schedule_id'])
			->from(['providing_schedule'=>Schedules::tableName()]);
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
		$items=$this->children??[];
		$result=$items;
		foreach ($items as $item) {
			$result=array_merge($result,$item->getChildrenRecursive());
		}
		return $result;
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
	
	/**
	 * @return ActiveQuery
	 */
	public function getResponsible()
	{
		return $this->hasOne(Users::class, ['id' => 'responsible_id'])
			->from(['responsible'=>Users::tableName()]);
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
	
	
	/**
	 * Возвращает команду поддержки инфраструктуры
	 */
	public function getInfrastructureSupport()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->from(['infrastructure_support'=>Users::tableName()])
			->viaTable('{{%users_in_svc_infrastructure}}', ['services_id' => 'id']);
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
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getManagedTechs()
	{
		return $this->hasMany(Techs::class, ['management_service_id' => 'id']);
	}
	/**
	 * Возвращает сопровождаемое этой услугой оборудование
	 */
	public function getSupportingTechs()
	{
		return $this->hasMany(Techs::class, ['management_service_id' => 'id']);
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
	 * Возвращает ос которые предоставляются этой услугой
	 */
	public function getProvideComps()
	{
		return $this->hasMany(Comps::class, ['platform_id' => 'id']);
	}
	
	
	public function getArms()
	{
		return $this->hasMany(Techs::class, ['id' => 'arm_id'])
			->from(['arms'=>Techs::tableName()])
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
	 * Привязанные сервисы
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])
			->from(['services_aces'=>Aces::tableName()])
			->viaTable('{{%services_in_aces}}', ['services_id' => 'id']);
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
	 * Возвращает ответственного за инфраструктуру исходя из пачки сервисов на узле
	 * @param      $services
	 * @param bool $ignoreIS //игонрировать ответственных за инфраструктуру при расчете
	 * @return Users|mixed|null
	 */
	public static function responsibleFrom($services,$ignoreIS=false) {
		$weightLimit= Yii::$app->params['support.service.min.weight']??0;
		if (is_array($services) && count($services)) {
			$persons=[];
			$rating=[];
			/** @var $service Services */
			foreach ($services as $service) if(!$service->archived) {
				
				$responsible=null;
				//сначала проверяем ответственного за инфраструктуру
				//(либо если он игнорируется то проверяем ответственного за сервисы, но только для "весомых" сервисов
				if ((!$ignoreIS || $weightLimit<$service->weight) && is_object($service->infrastructureResponsibleRecursive)) {
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
	
	
	
	public static function supportTeamFrom($services,$ignoreIS=false) {
		$team=[];
		if (is_array($services) && count($services)) {
			/** @var $service Services */
			foreach ($services as $service) {
				$weightLimit= Yii::$app->params['support.service.min.weight']??0;
				if(
					is_object($service)			//сервис есть
					&&							//и
					!$service->archived			//не в архиве
					&& (						//и
						!$weightLimit					//нет лимита на вес
						||								//или
						$weightLimit<$service->weight	//он превышен
					)
				) {
					
					$responsible=null;
					//сначала проверяем ответственного за инфраструктуру
					if (!$ignoreIS && is_object($service->infrastructureResponsibleRecursive)) {
						$responsible=$service->infrastructureResponsibleRecursive;
						//уже потом за сам сервис
					} elseif (is_object($service->responsibleRecursive)) {
						$responsible=$service->responsibleRecursive;
					}
					//ответственные за сервисы на машине
					if (is_object($responsible)) $team[$responsible->id]=$responsible;
					
					
					$support=[];
					//сначала проверяем ответственного за инфраструктуру
					if (!$ignoreIS && count($service->infrastructureSupportRecursive)) {
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
