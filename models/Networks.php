<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\traits\AclsFieldTrait;
use PhpIP;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;


/**
 * This is the model class for table "networks".
 *
 * @property int $id
 * @property string $name
 * @property string $sname
 * @property string $text_addr
 * @property string $text_dhcp
 * @property string $segmentCode
 * @property string $readableRouter
 * @property string $readableDhcp
 * @property string $comment
 * @property string $notepad
 * @property string $ranges
 * @property string $links
 * @property int $vlan_id
 * @property int $segments_id
 * @property int $addr
 * @property int $mask
 * @property int $capacity
 * @property int $used
 * @property int $router
 * @property int $usedPercent
 * @property int $dhcp
 * @property int $archived
 * @property array $dhcpList
 * @property array $rangesList
 * @property NetVlans $netVlan
 * @property NetDomains $netDomain
 * @property Segments $segment
 * @property NetIps $firstUnusedIp
 * @property Acls[] $acls
 * @property NetIps[] $ips
 * @property NetIps[] $ipsByAddr
 * @property OrgInet[] $orgInets
 * @property Places $place
 * @property Comps[] $comps
 * @property Techs[] $techs
 */
class Networks extends ArmsModel
{
	use AclsFieldTrait;
	private $_IPv4Block=null;
	private $ips_cache=null;
	private $first_unused_cache=null;
	private $dhcp_list_cache=null;
	private $ranges_cache=null;
	
	public static $titles='Сети';
	public static $title='Сеть';
	
	public static $latinNameHint='<br>'
	. '<i>'
	. '<b>Hint:</b> Рекомендуется написание латиницей без пробелов<br>'
	. 'чтобы можно было использовать сквозное именование в инвентаризации,<br>'
	. 'коммутаторах, маршрутизаторах, гипервизорах, фаерволах и т.п.'
	. '</i>';
	
	public $text_router;
	public $domain;
	
	
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'networks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['text_addr'],'ip','ipv6'=>false,'subnet'=>true],
			[['text_router'], 'ip','ipv6'=>false],
			['text_dhcp', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
            [['vlan_id', 'segments_id', 'addr', 'mask', 'router', 'dhcp','archived'], 'integer'],
            [['name'], 'string', 'max' => 255],
			[['comment','notepad','links','ranges'], 'safe'],
			[['segments_id'], 'exist', 'skipOnError' => true, 'targetClass' => Segments::class, 'targetAttribute' => ['segments_id' => 'id']],
		];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'id' => 'ID',
			'name' => [
				'Название сети',
				'hint' => 'Короткое понятное название' . static::$latinNameHint,
			],
			'vlan_id' => [
				'Vlan',
				'hint' => 'В каком Vlan находится эта сеть',
			],
			'vlan' => ['alias'=>'vlan_id'],
			'segments_id' => [
				Segments::$title,
				'hint' => 'К какому сегменту относится эта сеть',
			],
			'segment' => ['alias'=>'segments_id'],
			'domain_id' => [
				'Домен',
			],
			'domain' => ['alias'=>'domain_id'],
			'netDomain' => ['alias'=>'domain_id'],
			'addr' => [
				'Адрес',
				'hint' => 'Адрес сети (в понятной нотации 192.168.0.0)',
			],
			'usage' => [
				'Занято',
			],
			'text_addr' => [
				'Адрес и маска',
				'hint' => 'Адрес и маска сети (в десятичной нотации 192.168.1.0/24)',
			],
			'mask' => [
				'Маска',
				'hint' => 'Маска сети (в понятной нотации 255.255.255.0)',
			],
			'readableNetMask' => ['alias'=>'mask'],
			'router' => [
				'Шлюз',
				'hint' => 'Кто является шлюзом в сети (опционально)',
			],
			'text_router' => ['alias'=>'router'],
			'readableRouter' => ['alias'=>'router'],
			'dhcp' => [
				'DHCP сервер',
				'hint' => 'Кто является DHCP сервером (опционально)',
			],
			'readableDhcp' => ['alias'=>'dhcp'],
			'text_dhcp' => ['alias'=>'dhcp'],
			'comment' => [
				'Описание',
				'hint' => 'Короткое описание сети',
			],
			'ranges' => [
				'Диапазоны',
				'hint' => 'Диапазоны адресов для более удобного IPAM<br>'
					.'Например:<br>'
					.'1-29 Статика<br>'
					.'30-249 DHCP<br>'
					.'250-254 Резерв',
			],
			'notepad' => [
				'Подробно',
				'hint' => 'Подробное описание сети',
			],
			'readableWildcard' => ['Обратная маска'],
			'readableNetworkIp' => ['IP сети'],
			'readableFirstIp' => ['Первый доступный IP'],
			'readableLastIp' => ['Последний доступный IP'],
			'readableBroadcastIp' => ['Широковещательный IP'],
			'maxHosts' => ['Допустимое количество узлов'],
		]);
	}
	
	public function getPlace()
	{
		if (!is_object($this->netDomain)) return null;
		return $this->netDomain->place;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getNetVlan()
	{
		return $this->hasOne(NetVlans::class, ['id' => 'vlan_id']);
	}
	
	/**
	 * @return NetDomains
	 */
	public function getNetDomain()
	{
		return is_object($this->netVlan)?$this->netVlan->netDomain:null;
	}
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->text_addr. ($this->name?(' ('.$this->name.')'):'');
	}
	
	
	/**
	 * читаемый DHCP
	 * @return string
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getReadableDhcp()
	{
		return empty($this->dhcp)?'':long2ip($this->dhcp);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getOrgInets()
	{
		return $this->hasMany(OrgInet::class, ['id' => 'org_inets_id'])
			->viaTable('{{%org_inets_in_networks}}', ['networks_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['networks_id' => 'id']);
	}
	
	public function getComps()
	{
		if (isset($this->attrsCache['comps'])) return $this->attrsCache['comps'];
		$comps=[];
		foreach ($this->ips as $ip) {
			foreach ($ip->comps as $comp) {
				$comps[$comp->id]=$comp;
			}
		}
		return $this->attrsCache['comps']=$comps;
		/*return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->from(['ip_comps'=>Comps::tableName()])
			->viaTable('{{%ips_in_comps}}', ['ips_id' => 'id'])
			->via('ips');*/
	}
	
	public function getTechs()
	{
		if (isset($this->attrsCache['techs'])) return $this->attrsCache['techs'];
		$techs=[];
		foreach ($this->ips as $ip) {
			foreach ($ip->techs as $tech) {
				$techs[$tech->id]=$tech;
			}
		}
		return $this->attrsCache['techs']=$techs;
	}
	
	public function getIncomingAcesEffective()
	{
		

		if (isset($this->attrsCache['incomingAcesEffective']))
			return $this->attrsCache['incomingAcesEffective'];
		
		$this->attrsCache['incomingAcesEffective']=$this->getIncomingAces();
		
		foreach ($this->ips as $ip) {
			$this->attrsCache['incomingAcesEffective']=ArrayHelper::recursiveOverride(
				$this->attrsCache['incomingAcesEffective'],
				$ip->getIncomingAces()
			);
			
			foreach ($ip->comps as $comp) {
				$this->attrsCache['incomingAcesEffective']=ArrayHelper::recursiveOverride(
					$this->attrsCache['incomingAcesEffective'],
					$comp->getIncomingAces()
				);
				foreach ($comp->services as $service) {
					$this->attrsCache['incomingAcesEffective']=ArrayHelper::recursiveOverride(
						$this->attrsCache['incomingAcesEffective'],
						$service->getIncomingAces()
					);
				}
			}

			foreach ($ip->techs as $tech) {
				$this->attrsCache['incomingAcesEffective']=ArrayHelper::recursiveOverride(
					$this->attrsCache['incomingAcesEffective'],
					$tech->getIncomingAces()
				);
				foreach ($tech->services as $service) {
					$this->attrsCache['incomingAcesEffective']=ArrayHelper::recursiveOverride(
						$this->attrsCache['incomingAcesEffective'],
						$service->getIncomingAces()
					);
				}
			}
		}
		
		return $this->attrsCache['incomingAcesEffective'];
	}
	
	/**
	 * читаемый Router
	 * @return string
	 */
	public function getReadableRouter()
	{
		return empty($this->router)?'':long2ip($this->router);
	}
	
	/**
	 * емкость сети
	 * @return int|null
	 */
	public function getCapacity()
	{
		if (is_numeric($this->mask)) return (int)pow(2,(32-$this->mask));
		return null;
	}
	
	/**
	 * @return PhpIP\IPv4Block
	 */
	public function IPv4Block()
	{
		if (is_object($this->_IPv4Block)) return $this->_IPv4Block;
		return $this->_IPv4Block=PhpIP\IPv4Block::create($this->text_addr);
	}
	
	public function getReadableNetMask()
	{
		return $this->IPv4Block()->getMask()->humanReadable();
	}
	
	public function getReadableWildcard()
	{
		return $this->IPv4Block()->getMask()->bit_negate()->humanReadable();
	}
	
	public function getReadableNetworkIp()
	{
		return $this->IPv4Block()->getFirstIp()->humanReadable();
	}
	
	public function getReadableFirstIp()
	{
		return $this->IPv4Block()->getFirstIp()->plus(1)->humanReadable();
	}
	
	public function getReadableBroadcastIp()
	{
		return $this->IPv4Block()->getLastIp()->humanReadable();
	}
	
	public function getReadableLastIp()
	{
		return $this->IPv4Block()->getLastIp()->minus(1)->humanReadable();
	}
	
	public function containsIp($ip)
	{
		return $ip->isIn($this);
	}

	public function getMaxHosts()
	{
		return max(1,$this->capacity-2);
	}
	
	/**
	 * Network
	 * @return ActiveQuery|Networks
	 */
	public function getIps()
	{
		return $this->hasMany(NetIps::class, ['networks_id'=>'id'])->orderBy(['addr'=>SORT_ASC]);
	}
	
	public function getIpsByAddr()
	{
		if (is_null($this->ips_cache))
			$this->ips_cache=\yii\helpers\ArrayHelper::index($this->ips,'addr');

		return $this->ips_cache;
	}
	
	/**
	 * DHCP сервера в виде массива long
	 * @return array
	 */
	public function getDhcpList() {
		if (is_null($this->dhcp_list_cache))
			$this->dhcp_list_cache=NetIps::ipList2long($this->text_dhcp);
		return $this->dhcp_list_cache;
	}
	
	/**
	 * Возвращает поле ranges из множества строк вида 1-14 бла бла бла
	 * в виде массива из [1,14,"бла бла бла"]
	 * @return array
	 */
	public function getRangesList() {
		if (is_null($this->ranges_cache)) {
			$this->ranges_cache=[];
			$match=null;
			foreach (explode("\n",$this->ranges) as $line) {
				if (preg_match('/^(\d+)\s*-\s*(\d+)\s+(.+)$/',$line,$match)===1) {
					//убираем лишние пробелы
					$line=preg_replace('/^(\d+)\s*-\s*(\d+)\s+(.+)/', '\1-\2 \3', $line);
					$tokens = explode(' ', $line);
					$bounds = explode('-', $tokens[0]);
					unset ($tokens[0]);
					$this->ranges_cache[] = [
						$bounds[0],
						$bounds[1],
						trim(implode(' ', $tokens)),
					];
				}
			}
		}
		return $this->ranges_cache;
	}
	
	/**
	 * Первый свободный IP
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function getFirstUnusedIp()
	{
		if (is_null($this->first_unused_cache)) {
			$first=($this->capacity>=4)?1:0;
			$last=($this->capacity>=4)?$this->capacity:$this->capacity-1;
			for ($i=$first; $i<$last; $i++) {
				$addr=$this->addr+$i;
				if (!isset($this->ipsByAddr[$addr])) {
					$this->first_unused_cache=new NetIps();
					//$this->first_unused_cache->addr=$addr;
					$this->first_unused_cache->text_addr=long2ip($addr);
					$this->first_unused_cache->beforeSave(true);
					return $this->first_unused_cache;
				}
			}
			$this->first_unused_cache=false;
		}
		return $this->first_unused_cache;
	}
	
	/**
	 * @return ActiveQuery|Segments
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::class, ['id' => 'segments_id']);
	}
	
	/**
	 * CSS код сегмента к которому относится VLAN
	 * @return string
	 */
	public function getSegmentCode()
	{
		if (is_object($segment=$this->segment)) return $segment->code;
		return '';
	}
	
	/**
	 * занято адресов
	 * @return int|null
	 */
	public function getUsed()
	{
		if (is_array($this->ips)) return count($this->ips);
		return 0;
	}
	
	/**
	 * Процент использования сети
	 * @return int|null
	 */
	public function getUsedPercent()
	{
		if (is_numeric($this->capacity) && ($this->capacity>2) && is_numeric($this->used)) {
			return (int)(100 * $this->used / ($this->capacity));
		}
		return null;
	}
	
	/**
	 * Network
	 * @param null $addr
	 * @param null $mask
	 * @return NetIps[]
	 */
	public function findIps($addr=null,$mask=null)
	{
		if (is_null($addr)) $addr=$this->addr;
		if (is_null($mask)) $mask=$this->mask;
		return NetIps::find()->where(['AND',
			['>=','addr',$addr],
			['<','addr',$addr+(int)pow(2,(32-$mask))]
			])->all();
	}
	
	/**
	 * @param $i
	 * @return NetIps|null
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function fetchIp($i)
	{
		return $this->ipsByAddr[$this->addr+$i]??null;
		//foreach ($this->ips as $ip) if ($ip->addr == $this->addr+$i) return $ip;
		//return null;
	}
	
	/**
	 * Имея текстовый список IP/MASK возвращает ids объектов IP сетей
	 * @param      $text
	 * @return int[]
	 */
	public static function fetchNetworkIds($text) {
		if (!count($items=explode("\n",$text))) return[];
		$ids=[];
		foreach ($items as $item) {
			$item=trim($item);
			if (strlen($item)) {
				if (strpos($item,'/')!==false) {
					/** @var Networks $network */
					$network=static::find()->where(['text_addr'=>$item])->one();
					if (is_object($network))
						$ids[]=$network->id;
				}
				
			}
		}
		return $ids;
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			$addr=new PhpIP\IPv4Block($this->text_addr);
			$this->addr=$addr->getNetworkAddress()->numeric();
			$this->mask=$addr->getPrefix();
			$this->text_addr=$addr->getNetworkAddress()->humanReadable().'/'.$this->mask;
			
			if (!empty($this->text_router)) $this->router=ip2long($this->text_router);
			return true;
		}
		return false;
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		
		//NetIps::updateAll([])
		
		if ($insert || isset($changedAttributes['addr']) || isset($changedAttributes['mask'])) {
			$totalIps=[];
			
			//previous
			foreach ($this->ips as $ip)
				$totalIps[$ip->id]=$ip;
			
			//new
			foreach ($this->findIps() as $ip)
				$totalIps[$ip->id]=$ip;
			
			foreach ($totalIps as $ip) $ip->save();
		}
		
	}
	
	/**
	 * проверяет что все строки текстового атрибута $attribute, содержащие маску это существующие IP сети
	 * @param $model
	 * @param $attribute
	 */
	public static function validateInput(&$model,$attribute) {
		//сначала проверим, что тут нет посторонних строк, только адреса с маской или без
		NetIps::validateInput($model,$attribute);
		/** @var ArmsModel $model */
		//если есть строки которые вообще не являются адресами, то дальше не проверяем
		if ($model->hasErrors($attribute)) return;
		$items=explode("\n",$model->$attribute);
		foreach ($items as $item) if (strlen(trim($item))) {
			if (strpos($item, '/') !== false) {
				/** @var Networks $network */
				$network = static::find()->where(['text_addr' => $item])->one();
				if (!is_object($network))
					$model->addError($attribute, "Сеть $item не найдена в инвентаризации. Нельзя предоставить доступ не объявленной сети");
			}
		}
	}
	
	
	/** Возвращает список всех элементов
    * @return array|mixed|null
    */
	public static function fetchNames(){
		$list= static::find()
			//->joinWith('some_join')
			//->select(['id','name'])
			->orderBy(['text_addr'=>SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}
}
