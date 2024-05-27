<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\MacsHelper;
use app\helpers\QueryHelper;
use app\models\traits\CompsModelCalcFieldsTrait;
use Throwable;
use voskobovich\linker\LinkerBehavior;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * This is the model class for table "comps".
 *
 * @property int $id Идентификатор
 * @property int $domain_id Домен
 * @property int $sandbox_id Окружение
 * @property string $name Имя
 * @property string $os ОС
 * @property string $fqdn FQDN
 * @property string $raw_hw Отпечаток железа
 * @property string $raw_soft Отпечаток софта
 * @property string $raw_version Версия скрипта отправившего данные
 * @property string $exclude_hw Скрытое из паспорта железо
 * @property string $ignore_hw Игнорировать железо на машине
 * @property string $mac MAC адреса через перенос строки
 * @property string $formattedMac MAC адреса (приведенные к приличному виду) через перенос строки
 * @property string $ip IP адреса через перенос строки
 * @property string $ip_ignore Игнорировать IP адреса
 * @property int $arm_id Рабочее место
 * @property int $user_id Пользователь
 * @property string   $comment Комментарий
 * @property string   $updated_at Время обновления
 * @property boolean  $isIgnored Софт находится в списке игнорируемого ПО
 * @property array    $softHits_ids Массив ID ПО, которое установлено на компе
 * @property array    $soft_ids Массив ID ПО, которое внесено в паспорт
 * @property array    $netIps_ids Массив ID IP
 * @property array    $comps Массив объектов ПО, которое установлено на компе
 * @property boolean  $isWindows ОС относится к семейству Windows
 * @property boolean  $isLinux ОС относится к семейству Linux
 * @property boolean  $archived
 * @property Techs    $arm
 * @property Techs    $linkedArms
 * @property Comps[]  $dupes
 * @property Users    $user
 * @property Users                  $responsible
 * @property Users[]                $supportTeam
 * @property Domains                $domain
 * @property string                 $updatedRenderClass
 * @property string         $updatedText
 * @property string         $domainName
 * @property string         $currentIp
 * @property string[]       $ips
 * @property string[]       $ignoredIps
 * @property string[]       $filteredIps
 * @property LoginJournal[] $lastThreeLogins
 * @property LoginJournal[] $logins
 * @property NetIps[]       $netIps
 * @property Segments[]     $segments
 * @property HwList         $hwList
 * @property SwList         $swList
 * @property Services[]     $services
 * @property Places         $place
 * @property Acls[]         $acls
 * @property Aces[]         $aces
 * @property LicGroups[]    $licGroups
 * @property LicItems[]     $licItems
 * @property LicKeys[]      $licKeys
 * @property Soft[]         $soft
 * @property MaintenanceReqs $maintenanceReqs
 * @property MaintenanceJobs $maintenanceJobs
 * @property MaintenanceReqs $effectiveMaintenanceReqs
 * @property ServiceConnections $incomingConnections
 * @property ServiceConnections $outgoingConnections
 * @property ServiceConnections $incomingConnectionsEffective
 * @property ServiceConnections $outgoingConnectionsEffective
 * @property Sandboxes $sandbox
 */
class Comps extends ArmsModel
{
	use CompsModelCalcFieldsTrait;
	
	public static $title='Операционная система';
	public static $titles='Операционные системы';
    private $hwList_obj=null;
    private $swList_obj=null;
    private $ip_cache=null;
	private $ip_ignore_cache=null;
	private $ip_filtered_cache=null;
	private $servicePartialWeightCache=[];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comps';
    }
    
    public function extraFields()
	{
		return ['responsible','supportTeam','fqdn','domain','site','place','arm','services','servicesNames','sandbox'];
	}
	
	public $linksSchema=[
		'arm_id' =>				Techs::class,
		'domain_id' =>			Domains::class,
		'user_id' =>			Users::class,
		'sandbox_id' =>			Sandboxes::class,
		
		'linked_arms_ids'=>		[Techs::class,'comp_id'],
		'services_ids'=>		[Services::class,'comps_ids'],
		'aces_ids'=>			[Aces::class,'comps_ids'],
		'acls_ids'=>			[Acls::class,'comp_ids'],
		'lic_groups_ids' =>		[LicGroups::class,'comp_ids'],
		'lic_items_ids' =>		[LicItems::class,'comp_ids'],
		'lic_keys_ids' =>		[LicKeys::class,'comp_ids'],
		
		'maintenance_reqs_ids'=>[MaintenanceReqs::class,'comps_ids'],
		'maintenance_jobs_ids'=>[MaintenanceJobs::class,'comps_ids'],
	];

    /**
     * @inheritdoc
	 */
    public function rules()
    {
        return [
			['name', 'filter', 'filter' => function ($value) {
				return Domains::validateHostname($value,$this);
			}],
            [['soft_ids','netIps_ids','services_ids','maintenance_reqs_ids','maintenance_jobs_ids'], 'each', 'rule'=>['integer']],
            [['name', 'os','domain_id'], 'required'],
			[['sandbox_id'],'default','value'=>null],
            [['domain_id', 'arm_id', 'ignore_hw', 'user_id','archived','sandbox_id'], 'integer'],
            [['raw_hw', 'raw_soft','exclude_hw','raw_version'], 'string'],
            [['updated_at', 'comment','external_links'], 'safe'],
			[['raw_version'], 'string', 'max' => 32],
            [['name','os'], 'string', 'max' => 128],
			[['ip', 'mac'], 'string', 'max' => 768],
			[['ip_ignore'], 'string', 'max' => 512],
			
			['ip', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
			
			['mac', 'filter', 'filter' => function ($value) {
				return MacsHelper::fixList($value);
			}],
	
			[
				['domain_id', 'name', 'sandbox_id'],
				'unique',
				'targetAttribute' => ['domain_id', 'name', 'sandbox_id'],
				'skipOnEmpty'=>false,
				'message' => 'В этом домене этого окружения/песочницы уже есть такой hostname'
			],
			[['arm_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::class, 'targetAttribute' => ['arm_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'id']],
            [['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::class, 'targetAttribute' => ['domain_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'mac' => [
				'MAC Адрес',
				'indexHint' => 'MAC адреса сетевых интерфейсов настроенных в ОС<br/>'.QueryHelper::$stringSearchHint,
			],
			'ip' => [
				'IP Адрес',
				'indexHint' => 'IP адреса сетевых интерфейсов настроенных в ОС<br/>'.QueryHelper::$stringSearchHint,
			],
			'ip_ignore' => ['absorb'=>'ifEmpty'],
			'domain_id' => ['Домен','absorb'=>'ifEmpty'],
			'user_id' => [
				'Пользователь',
				'hint' => 'Имеет смысл только для серверов и виртуальных машин в случае, '
					.'<br>если пользователь операционной системы отличается от пользователя АРМ',
				'absorb'=>'ifEmpty'
			],
			'user' => ['alias'=>'user'],
            'name' => [
            	'Имя компьютера',
				'indexHint' => 'Сетевое имя компьютера настроенное в ОС.<br>'.
					'Домен не выводится, но при поиске можно указывать.<br>'.
					'Вводимый текст ищется в строке формата DOMAIN\\computer<br/>'.QueryHelper::$stringSearchHint,
			],
            'os' => [
            	'Наименование и версия операционной системы',
				'indexHint' => 'В таблице в этой ячейке выводится только наименование ОС,<br>'.
					'но поиск ведется также и по софту (в сыром, а не отформатированном виде)<br/>'.
					QueryHelper::$stringSearchHint,
			],
			'raw_hw' => [
				'Hardware',
				'indexHint' => 'Строка оборудования обнаруженного Операционной Системой<br>'.
					'Чтобы увидеть оборудование в отформатированном виде - наведите мышку на строку<br/>'.
					QueryHelper::$stringSearchHint,
			],
	        'raw_soft' => 'Отпечаток софта (заполняется скриптом)',
	        'raw_version' => [
	        	'Скрипт',
				'indexHint' => 'Скрипт, который внес последние данные по этой ОС<br/>'.QueryHelper::$stringSearchHint,
			],
            'exclude_hw' => ['Скрытое из паспорта железо','absorb'=>'ifEmpty'],
            'ignore_hw' => ['Виртуальная машина','absorb'=>'ifEmpty'],
            'arm_id' => [
            	'АРМ',
				'indexHint' => 'ПК на котором установлена ОС<br/>'.QueryHelper::$stringSearchHint,
				'absorb'=>'ifEmpty',
			],
			'sandbox_id'=>[
				'placeholder'=>'ОС не изолирована в песочнице',
				'hint' => 'Изолированное окружение в которое помещена ОС.<br/>'
						.'Позволяет вести учет клонов/ копий ВМ восстановленных из архива и т.п.'
			],
			'services_ids' => [
				'Сервисы',
				'hint' => 'Какие сервисы развернуты на этой ОС',
				'indexHint' => '{same}<br />'.QueryHelper::$stringSearchHint,
			],
            'comment' => ['Комментарий','absorb'=>'ifEmpty'],
            'updated_at' => 'Время обновления',
			'archived' => [
				'Архивирован',
				'hint'=>'Если эта ОС уже не используется, но на нее есть ссылки из других объектов <br />'.
					'(например есть заархивированный сервис, который был развернут на этой ос),<br />'.
					'то можно не удалять ее, а заархивировать, чтобы не разрушать взаимосвязи объектов<br />'.
					'ОС останется в БД для истории, но не будет попадаться на глаза, если явно не попросить'
			],
			'vCpuCores' => [
				'vCPU',
				'indexHint' => 'Количество CPU ядер VM'
			],
			'vRamGb' => [
				'vRAM',
				'indexHint' => 'Оперативная память VM (GB)'
			],
			'vHddGb' => [
				'vHDD',
				'indexHint' => 'Объем дискового пространства (GB)'
			],
			'places_id' => [
				'Помещение',
				'indexHint' => 'Помещение, в котором размещено оборудование',
				'absorb'=>'ifEmpty',
			],
			'maintenance_reqs_ids'=>[
				MaintenanceReqs::$titles,
				'hint'=>'Какие предъявлены требования по обслуживанию ОС/ВМ.'
					.'<br>По хорошему требования должны предъявлять сервисы, '
					.'<br>работающие на ОС/ВМ, но можно задать их и явно',
				'indexHint'=>'{same}'
			],
			'maintenanceReqs'=>['alias'=>'maintenance_reqs_ids'],
			'effectiveMaintenanceReqs'=>[
				MaintenanceReqs::$titles,
				'indexLabel'=>'Треб. обслуживания',
				'indexHint'=>'Какие предъявлены требования по обслуживанию.'
					.'<br>Как распространенные с сервисов, так и заданные явно. '
					.'<br>Избыточно предъявленные требования помечаются как "архивные"'
			],
			'maintenance_jobs_ids'=>[
				MaintenanceJobs::$titles,
				'hint'=>'Какие операции регламентного обслуживания проводятся над этой ОС/ВМ',
				'indexHint'=>'{same}'
			],
			'maintenanceJobs'=>['alias'=>'maintenance_jobs_ids'],
			'lics' => [
				'Лицензии',
				'hint' => 'Все привязанные лицензии:<br>Типы лицензий, закупки, ключи',
				'indexHint' => '{same}',
			]

		]);
    }


    public function behaviors()
    {
        return [
            [
                'class' => LinkerBehavior::class,
                'relations' => [
					'soft_ids' => [
						'soft',
						'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'softHits_ids' => [
						'softHits',
						'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'netIps_ids' => 'netIps',
					'services_ids' => 'services',
					'aces_ids' => 'aces',
					'acls_ids' => 'acls',	//one-2-many
					'lic_groups_ids' => 'licGroups',
					'lic_items_ids' => 'licItems',
					'lic_keys_ids' => 'licKeys',
					'maintenance_reqs_ids' => 'maintenanceReqs',
					'maintenance_jobs_ids' => 'maintenanceJobs',
                ]
            ]
        ];
    }
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getArm()
	{
		return $this->hasOne(Techs::class, ['id' => 'arm_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getLinkedArms()
	{
		return $this->hasMany(Techs::class, ['comp_id' => 'id']);
	}
	
	/**
	 * Входящие соединения (явно объявленные на этот комп)
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getIncomingConnections()
	{
		return $this->hasMany(ServiceConnections::class, ['id' => 'connection_id'])
			->viaTable('comps_in_targets', ['comps_id' => 'id']);
	}
	
	/**
	 * Исходящие соединения (явно объявленные с этого компа)
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getOutgoingConnections()
	{
		return $this->hasMany(ServiceConnections::class, ['id' => 'connection_id'])
			->viaTable('comps_in_initiators', ['comps_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(Users::class, ['id' => 'user_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getDomain()
	{
		return $this->hasOne(Domains::class, ['id' => 'domain_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getSandbox()
	{
		return $this->hasOne(Sandboxes::class, ['id' => 'sandbox_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getDupes()
	{
		return $this->hasmany(Comps::class, ['name' => 'name'])
			->where(['not',['id'=>$this->id]])
			->andWhere(['sandbox_id'=>$this->sandbox_id]);
	}
	
	
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getSoft()
	{
		return $this->hasMany(Soft::class, ['id' => 'soft_id'])
			->viaTable('{{%soft_in_comps}}', ['comp_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getSoftHits()
	{
		return $this->hasMany(Soft::class, ['id' => 'soft_id'])
			->from(['installed_soft'=>Soft::tableName()])
			->viaTable('{{%soft_hits}}', ['comp_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])
			->viaTable('{{%comps_in_services}}', ['comps_id' => 'id']);
	}
	
	//нужно только для сортировки моделей внутри ArrayDataProvider
	public function getServicesNames() {
		$names=ArrayHelper::getColumn($this->services,'name',false);
		sort($names);
		return implode('',$names);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::class, ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_comps}}', ['comps_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_comps}}', ['comps_id' => 'id']);
	}

	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::class, ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_comps}}', ['comps_id' => 'id']);
	}
	
	/**
	 * Найти комп по полному имени (Domain\comp или comp.domain.local)
	 * @param $name
	 * @return ActiveRecord|Comps|null|false
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	public static function findByAnyName($name) {
		$nameParse=Domains::fetchFromCompName($name);
		if (!is_array($nameParse)) return false;	//ошибка формата имени компа
		[$domain_id,$compName,$domainName]=$nameParse;
		if (is_null($domain_id)) return null;		//не найден домен => не найден комп в этом домене

		$filter=['LOWER(name)'=>strtolower($compName)];
		if ($domain_id!==false) $filter['domain_id']=$domain_id;
		
		return static::find()->where($filter)->one();
	}
	
	public function getLastThreeLogins() {
		return LoginJournal::fetchUniqUsers($this->id);
	}
	
	public function getLogins() {
		return $this->hasmany(LoginJournal::class, ['comps_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['comps_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])->from(['comp_aces'=>Aces::tableName()])
			->viaTable('{{%comps_in_aces}}', ['comps_id' => 'id']);
	}
	
	public function getMaintenanceReqs()
	{
		return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])
			->viaTable('maintenance_reqs_in_comps', ['comps_id' => 'id']);
	}
	
	public function getMaintenanceJobs()
	{
		return $this->hasMany(MaintenanceJobs::class, ['id' => 'jobs_id'])
			->viaTable('maintenance_jobs_in_comps', ['comps_id' => 'id']);
	}

	
	/**
	 * @return ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id'])//->from(['comp_places'=>Places::tableName()])
			->via('arm');
	}
	
	
	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])->from(NetIps::tableName())
			->viaTable('{{%ips_in_comps}}', ['comps_id' => 'id']);
	}
	
	
	
	public function getUpdatedRenderClass() {
		if (strlen($this->updated_at)) {
			$data_age=$this->secondsSinceUpdate;
			if ($data_age < 3600) return 'hour_fresh';
			elseif ($data_age < 3600*24) return 'day_fresh';
			elseif ($data_age < 3600*24*7) return 'week_fresh';
			elseif ($data_age < 3600*24*30) return 'month_fresh';
			else return 'over_month_fresh';
		} else return '';
	}
	
	public function getUpdatedText() {
		if (strlen($this->updated_at)) {
			$data_age=$this->secondsSinceUpdate;
			if ($data_age < 3600) return (int)($data_age/60).' мин.';
			elseif ($data_age < 3600*72) return (int)($data_age/3600).' ч.';
			else return (int)($data_age/3600/24).' дн.';
		} else return '';
	}
	
	/**
	 * Возвращает долю веса сервиса (с учетом дочерних)
	 * @param $serviceId
	 * @return float|int|mixed
	 */
	public function recursiveServicePartialWeight($serviceId) {
		if (isset($this->servicePartialWeightCache[$serviceId]))
			return $this->servicePartialWeightCache[$serviceId];
		$total=0;
		$current=0;
		foreach ($this->services as $service) {
			$total+=$service->weight;
			if ($service->inService($serviceId)) {
				$current+=$service->weight;
			}
		}
		
		if (!$total)
			$this->servicePartialWeightCache[$serviceId]=0; //no services
		else
			$this->servicePartialWeightCache[$serviceId]=$current/$total;
		
		return $this->servicePartialWeightCache[$serviceId];
	}
	
	/**
	 * @return Users
	 */
	public function getResponsible()
	{
		if (is_object($this->user)) return $this->user;
		
		return Services::responsibleFrom($this->services);
	}
	
	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection UnusedElement
	 */
	public function getSupportTeam()
	{
		$team=Services::supportTeamFrom($this->services);
		if (is_object($this->user)) $team[$this->user->id]=$this->user;
		
		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->responsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}
		
		return array_values($team);
	}
	
	
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
	/**
	 * @param Comps $comp
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
	public function absorbComp(Comps $comp) {
		
		//журнал огромный и по одной записи менять это гемор
		LoginJournal::updateAll(['comps_id'=>$this->id],['users_id'=>$comp->id]);
		
		//полглощаем все поля и ссылки переданной ОС и удаляем ее
		$this->absorbModel($comp,true);
		$this->save();
	}
	

	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (!Soft::$disable_rescan) //если только автообновление привязок не блокировано
			$this->softHits_ids=array_keys($this->swList->items);

			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ip);
			
			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {

				/* Взаимодействие с АРМ */

				//если поменялся АРМ, то надо из старого АРМа выкинуть эту ОСь
				if (!is_null($old->arm) && ($old->arm_id != $this->arm_id)) {
					
					//если у старого АРМа не только эта операционка привязана - назначим основной другую
					if (count($old->arm->comps) > 1) {
						foreach ($old->arm->comps as $comp) {
							if ($comp->id != $this->id) {
								$old->arm->comp_id = $comp->id;
								break;
							}
						}
					} else {
						//иначе удаляем в старом АРМ основную ОС
						$old->arm->comp_id = null;
					}
					//сохраняем старый арм
					$old->arm->save();
				}

				//находим все IP адреса которые от этой ОС отвалились
				$removed=array_diff($old->netIps_ids,$this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachComp($this->id);
				}
			}
			

		}
		return true;
	}

	
	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}
		
		//отключаем рескан чтобы при сохранении софт не привязался обратно
		Soft::$disable_rescan=true;
		$this->softHits_ids=[];
		$this->silentSave(false);
		
		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachComp($this->id);
		}
		
		foreach ($this->linkedArms as $arm) {
			$arm->comp_id=null;
			$arm->save();
		}
		
		return true;
	}
	
	/**
	 * @inheritdoc
	 */
	public function afterSave($insert,$changedAttributes)
	{
		parent::afterSave($insert,$changedAttributes);
		//если в новом арме не назначена основная ОС, то назначим эту
		if (!is_null($this->arm_id)) {
			if (is_object($arm=$this->arm)) {
				if (empty($arm->comp_id)) {
					$arm->comp_id=$this->id;
					$arm->save();
				}
			}
		}
		/*
		 * Если у этой есть МАК и это не виртуалка
		 * и есть АРМы ссылающиеся на эту ОС
		 * и у них МАК пустой
		 * тогда вписываем им МАК от этой ОС
		 */
		if ($this->mac && !$this->ignore_hw) {
			foreach ($this->linkedArms as $arm) {
				if (empty($arm->mac)) {
					$arm->mac=$this->mac;
					$arm->save();
				}
			}
		}
		return true;
	}
	
	public function getIsWindows()
	{
		return (mb_stripos($this->os,'windows')!==false);
	}
	
	public function getIsLinux()
	{
		if (mb_stripos($this->os,'debian')!==false) return true;
		if (mb_stripos($this->os,'centos')!==false) return true;
		if (mb_stripos($this->os,'ubuntu')!==false) return true;
		if (mb_stripos($this->os,'fedora')!==false) return true;
		if (mb_stripos($this->os,'red hat')!==false) return true;
		if (mb_stripos($this->os,'suse')!==false) return true;
		return false;
	}
	
	public function getInServicesName() {return strtolower($this->fqdn);}
	
}
