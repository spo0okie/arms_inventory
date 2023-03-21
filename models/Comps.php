<?php

namespace app\models;

use app\helpers\QueryHelper;
use DateTime;
use DateTimeZone;
use Yii;

/**
 * This is the model class for table "comps".
 *
 * @property int $id Идентификатор
 * @property int $domain_id Домен
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
 * @property array    $soft_ids Массив ID ПО, которое установлено на компе
 * @property array    $netIps_ids Массив ID IP
 * @property array    $comps Массив объектов ПО, которое установлено на компе
 * @property boolean  $isWindows ОС относится к семейству Windows
 * @property boolean  $isLinux ОС относится к семейству Linux
 * @property boolean  $archived
 * @property Techs  $arm
 * @property Techs  $mainArm
 * @property Comps[]  $dupes
 * @property Users    $user
 * @property Users    $responsible
 * @property Users[]  $supportTeam
 * @property Domains  $domain
 * @property string   $updatedRenderClass
 * @property string   $updatedText
 * @property string   $domainName
 * @property string   $currentIp
 * @property string[] $ips
 * @property string[] $ignoredIps
 * @property string[] $filteredIps
 * @property \app\models\LoginJournal[] $lastThreeLogins
 * @property \app\models\LoginJournal[] $logins
 * @property \app\models\NetIps[] $netIps
 * @property Segments[] $segments
 * @property \app\models\HwList $hwList
 * @property \app\models\SwList $swList
 * @property \app\models\Services $services
 * @property Places $place
 * @property Acls[] $acls
 * @property Aces[] $aces
 * @property LicGroups[] $licGroups
 * @property LicItems[] $licItems
 * @property LicKeys[] $licKeys
 */
class Comps extends ArmsModel
{
	
	public static $title='Операционная система';
	public static $titles='Операционные системы';
    private $hwList_obj=null;
    private $swList_obj=null;
    private $ip_cache=null;
	private $ip_ignore_cache=null;
	private $ip_filtered_cache=null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comps';
    }
    
    public function extraFields()
	{
		return ['responsible','supportTeam','fqdn','domain','site','place','arm'];
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['soft_ids','netIps_ids'], 'each', 'rule'=>['integer']],
            [['domain_id','name', 'os'], 'required'],
            [['domain_id', 'arm_id', 'ignore_hw', 'user_id','archived'], 'integer'],
            [['raw_hw', 'raw_soft','exclude_hw','raw_version'], 'string'],
            [['updated_at', 'comment'], 'safe'],
			[['raw_version'], 'string', 'max' => 32],
            [['name','os'], 'string', 'max' => 128],
	        [['ip', 'ip_ignore','mac'], 'string', 'max' => 512],
			/* Валидация отключена, т.к. ввод данных в основном автоматический (скриптами)
			и если ее включить, данные просто не будут приниматься, что весьма плохо
			['ip', function ($attribute, $params, $validator) {
				\app\models\NetIps::validateInput($this,$attribute);
			}],
			вместо этого мы их фильтруем выкидывая неверные значения (ниже)*/
			['ip', 'filter', 'filter' => function ($value) {
				return \app\models\NetIps::filterInput($value);
			}],
			
			['mac', 'filter', 'filter' => function ($value) {
				$macs=explode("\n",$value);
				foreach ($macs as $i=>$mac) {
					$macs[$i]=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));
				}
				return implode("\n",$macs);;
			}],
	
			[['domain_id', 'name'], 'unique', 'targetAttribute' => ['domain_id', 'name']],
			[['arm_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['arm_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::className(), 'targetAttribute' => ['domain_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return [
	        'id' => 'Идентификатор',
			'mac' => [
				'MAC Адрес',
				'indexHint' => 'MAC адреса сетевых интерфейсов настроенных в ОС<br/>'.QueryHelper::$stringSearchHint,
			],
			'ip' => [
				'IP Адрес',
				'indexHint' => 'IP адреса сетевых интерфейсов настроенных в ОС<br/>'.QueryHelper::$stringSearchHint,
			],
			'domain_id' => 'Домен',
			'user_id' => 'Пользователь',
			'user' => 'Пользователь',
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
            'exclude_hw' => 'Скрытое из паспорта железо',
            'ignore_hw' => 'Виртуальная ОС',
            'arm_id' => [
            	'АРМ',
				'indexHint' => 'ПК на котором установлена ОС<br/>'.QueryHelper::$stringSearchHint,
			],
            'comment' => 'Комментарий',
            'updated_at' => 'Время обновления',
			'archived' => [
				'Архивирован',
				'hint'=>'Если эта ОС уже не используется, но на нее есть ссылки из других объектов,<br />'.
					'например если есть заархивированный сервис который был развернут на этой ос,<br />'.
					'то можно не удалять ее, а заархивировать, чтобы не разрушать взаимосвязи объектов<br />'.
					'ОС останется в БД для истории, но не будет попадаться на глаза, если явно не попросить'
			]

		];
    }


    public function behaviors()
    {
        return [
            [
                'class' => \voskobovich\linker\LinkerBehavior::className(),
                'relations' => [
					'soft_ids' => 'soft',
					'netIps_ids' => 'netIps',
                ]
            ]
        ];
    }
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArm()
	{
		return $this->hasOne(Techs::class, ['id' => 'arm_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMainArm()
	{
		return $this->hasOne(Techs::class, ['comp_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(Users::className(), ['id' => 'user_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getDomain()
	{
		return $this->hasOne(Domains::className(), ['id' => 'domain_id']);
	}
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getDupes()
	{
		return $this->hasmany(Comps::className(), ['name' => 'name'])
			->where(['not',['id'=>$this->id]]);
	}
	
	
	/**
     * @return string
     */
    public function getDomainName()
    {
        return (is_object($this->domain)?$this->domain->name:'').
	        '\\'.$this->name;
    }


	public function getFqdn()
	{
		return is_object($this->domain)?$this->name.'.'.$this->domain->fqdn:$this->name;
	}

    /**
     * Возвращает отпечатки исключенного из паспорта оборудования в виде массива
     */
    public function getExcludeHwArray()
    {
        $arrExcluded = explode("\n",$this->exclude_hw);
        foreach ($arrExcluded as $i => $item) {
            $arrExcluded[$i]=trim($item);
        }
        return $arrExcluded;
    }

    public function addExclusion($item)
    {
        return $this->exclude_hw=implode("\n",array_merge($this->getExcludeHwArray(),[$item]));
    }

    public function subExclusion($item)
    {
        return $this->exclude_hw=implode("\n",array_diff($this->getExcludeHwArray(),[$item]));
    }

    /**
     * Возвращает все оборудование в виде HwList
     */
    public function getHwList()
    {

        if (!is_null($this->hwList_obj)) return $this->hwList_obj;
        $this->hwList_obj = new HwList();
        $this->hwList_obj->loadRaw($this->raw_hw);
        return $this->hwList_obj;
    }

    /**
     * Возвращает весь софт в виде SwList
     */
    public function getSwList()
    {
        if (!is_null($this->swList_obj)) return $this->swList_obj;
        $this->swList_obj = new SwList();
        $this->swList_obj->loadItems($this->soft_ids);
        $this->swList_obj->loadRaw($this->raw_soft);
        return $this->swList_obj;
    }

    public function getHardArray()
    {
        return $this->hwList->items;
    }


    /**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array $items список элементов для вывода
     * @param string $keyField поле - ключ
     * @param string $valueField поле - значение
     * @param bool $asArray
     * @return array
     */
    public static function listItems($items=null, $keyField = 'id', $valueField = 'name', $asArray = true)
    {

        $query = static::find();
        if (!is_null($items)) $query->filterWhere(['id'=>$items]);
        if ($asArray) $query->select([$keyField, $valueField])->asArray();

        return \yii\helpers\ArrayHelper::map($query->all(), $keyField, $valueField);
    }

	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getSoft()
	{
		return static::getDb()->cache(function($db) {return $this->hasMany(Soft::className(), ['id' => 'soft_id'])
			->viaTable('{{%soft_in_comps}}', ['comp_id' => 'id']);},Manufacturers::$CACHE_TIME);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['id' => 'services_id'])
			->viaTable('{{%comps_in_services}}', ['comps_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::className(), ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_comps}}', ['comps_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::className(), ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_comps}}', ['comps_id' => 'id']);
	}

	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::className(), ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_comps}}', ['comps_id' => 'id']);
	}
	
	
	public static function findByDomainName($domain_id,$name){
		$name=mb_strtolower($name);
		//error_log("searching Domain Name: $domain_id $name");
		$list = static::find()->select(['id','domain_id','name'])->asArray(true)->all();
		//var_dump($list);
		foreach ($list as $item) {
			if ($item['domain_id']==$domain_id && !strcmp(mb_strtolower($item['name']),$name)) return $item['id'];
			//if ($item->domain_id==$domain_id && !strcmp(mb_strtolower($item->name),$name)) return $item->id;
		}
		return null;
	}
	
	public function getLastThreeLogins() {
		return \app\models\LoginJournal::fetchUniqUsers($this->id);
	}
	
	public function getLogins() {
		return $this->hasmany(LoginJournal::className(), ['comps_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::className(), ['comps_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::className(), ['id' => 'aces_id'])->from(['comp_aces'=>Aces::tableName()])
			->viaTable('{{%comps_in_aces}}', ['comps_id' => 'id']);
	}
	
	
	//список адресов, которые вернул скрипт инвентаризации
	public function getIps() {
		if (!is_null($this->ip_cache)) return $this->ip_cache;
		$this->ip_cache=explode("\n",$this->ip);
		foreach ($this->ip_cache as $i=>$ip) $this->ip_cache[$i]=trim($ip);
		$this->ip_cache=array_unique($this->ip_cache);
		return $this->ip_cache;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::className(), ['id' => 'places_id'])//->from(['comp_places'=>Places::tableName()])
			->via('arm');
	}
	
	public function getSite()
	{
		return is_object($this->place)?$this->place->top:null;
	}
	
	
	public function getSegments() {
		$segments=[];
		foreach ($this->filteredIps as $ip)
			if (is_object($ip)){;
				if (is_object($segment=$ip->segment))
					$segments[$segment->id]=$segment;
			}
		return $segments;
	}

	//фильтр наложенный пользователем
	public function getIgnoredIps() {
		if (!is_null($this->ip_ignore_cache)) return $this->ip_ignore_cache;
		$this->ip_ignore_cache=explode("\n",$this->ip_ignore);
		foreach ($this->ip_ignore_cache as $i=>$ip) $this->ip_ignore_cache[$i]=trim($ip);
		$this->ip_ignore_cache=array_unique($this->ip_ignore_cache);
		return $this->ip_ignore_cache;
	}

	//отфильтрованные адреса
	public function getFilteredIps() {
		if (!is_null($this->ip_filtered_cache)) return $this->ip_filtered_cache;
		$this->ip_filtered_cache=array_unique(array_diff($this->ips,$this->ignoredIps));
		return $this->ip_filtered_cache;
	}

	public function getFilteredIpsStr() {
		return implode(',', $this->filteredIps);
	}

	public function getCurrentIp() {
    	if (count($this->filteredIps)) return array_values($this->filteredIps)[0];
    	return '';
	}
	
	
	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::className(), ['id' => 'ips_id'])->from(NetIps::tableName())
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
	 * @return \app\models\Users
	 */
	public function getResponsible()
	{
		if (is_object($this->user)) return $this->user;
		
		if (is_array($this->services) && count($this->services)) {
			$persons=[];
			$rating=[];
			foreach ($this->services as $service) {
				/**
				 * @var $service \app\models\Services
				 */
				
				if (is_object($responsible=$service->responsibleRecursive)) {
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
	
	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return \app\models\Users
	 */
	public function getSupportTeam()
	{
		$team=[];
		if (is_object($this->user)) $team[$this->user->id]=$this->user;
		
		if (is_array($this->services) && count($this->services)) {
			foreach ($this->services as $service) {
				/**
				 * @var $service \app\models\Services
				 */
				
				//ответственные за сервисы на машине
				if (is_object($responsible=$service->responsibleRecursive)) {
					$team[$responsible->id]=$responsible;
				}
				
				//поддержка сервисов на машине
				if (is_array($support=$service->supportRecursive)) {
					foreach ($support as $item) {
						if (is_object($item))
							$team[$item->id]=$item;
					}
				}
			}
		}
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
	 */
	public function absorbComp($comp) {
		$fields=[
			'domain_id',
			'os',
			'fqdn',
			'raw_hw',
			'raw_soft',
			'raw_version',
			'exclude_hw',
			'ignore_hw',
			'ip',
			'ip_ignore',
			'arm_id',
			'user_id',
			'comment',
			'updated_at',
		];
		foreach ($fields as $field) {
			if ((empty($this->$field) || !$this->$field) && !empty($comp->$field)) {
				//error_log("absorbing [$field] '{$comp->$field}' -> '{$this->$field}'");
				$this->$field=$comp->$field;
			} //else error_log(  "skipping  [$field] '{$comp->$field}' -> '{$this->$field}'");
			
		}
		
		foreach ($comp->logins as $login) {
			$login->comps_id=$this->id;
			$login->save(false);
		}
		
		foreach ($comp->services as $service) {
			$serviceComps=$service->comps_ids;
			if (($key = array_search($comp->id, $serviceComps)) !== false) {
				//отрываем поглощаемый комп
				unset($serviceComps[$key]);
			}
			
			if (($key = array_search($this->id, $serviceComps)) === false) {
				//привязываем этот комп
				$serviceComps[]=$this->id;
			}

			$service->comps_ids=$serviceComps;
			//сохраняем изменения
			$service->save();
		}
		
		$comp->delete();
		$this->save();
	}
	
	public function renderName($fqdn=false)
	{
		return $fqdn?mb_strtolower($this->fqdn):mb_strtoupper($this->name);
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			

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
				
				/* убираем посторонние символы из MAC*/
				$macs=explode("\n",$this->mac);
				foreach ($macs as $i=>$mac) {
					$macs[$i]=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));
				}
				$this->mac=implode("\n",$macs);
				
				/* взаимодействие с NetIPs */
				$this->netIps_ids=NetIps::fetchIpIds($this->ip);
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

	public function getFormattedMac() {
		
		return \app\models\Techs::formatMacs($this->mac);
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}
		
		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachComp($this->id);
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
		if ($this->mac && is_object($arm=$this->mainArm)) {
			if (empty($arm->mac)) {
				$arm->mac=$this->mac;
				$arm->save();
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
}
