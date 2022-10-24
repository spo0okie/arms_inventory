<?php

namespace app\models;

use PhpIP;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;


/**
 * This is the model class for table "networks".
 *
 * @property int $id
 * @property string $name
 * @property string $sname
 * @property string $text_addr
 * @property string $segmentCode
 * @property string $readableRouter
 * @property string $readableDhcp
 * @property string $comment
 * @property int $vlan_id
 * @property int $segments_id
 * @property int $addr
 * @property int $mask
 * @property int $capacity
 * @property int $used
 * @property int $router
 * @property int $usedPercent
 * @property int $dhcp
 * @property NetVlans $netVlan
 * @property NetDomains $netDomain
 * @property Segments $segment
 * @property NetIps[] $ips
 * @property Places $place
 */
class Networks extends ArmsModel
{
	
	private $_IPv4Block=null;
	private $ips_cache=null;
	
	public static $title='Сети';
	public $text_dhcp;
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
			[['text_router','text_dhcp'], 'ip','ipv6'=>false],
            [['vlan_id', 'segments_id', 'addr', 'mask', 'router', 'dhcp'], 'integer'],
            [['name'], 'string', 'max' => 255],
			[['comment'], 'safe'],
			[['segments_id'], 'exist', 'skipOnError' => true, 'targetClass' => Segments::className(), 'targetAttribute' => ['segments_id' => 'id']],
		];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'id' => 'ID',
			'name' => [
				'Название сети',
				'hint' => 'Короткое понятное название',
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
				'Исп.',
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
				'Пояснение',
				'hint' => 'Все что нужно знать про сеть сверх того, что уже внесено выше',
			],
			'readableWildcard' => ['Обратная маска'],
			'readableNetworkIp' => ['IP сети'],
			'readableFirstIp' => ['Первый доступный IP'],
			'readableLastIp' => ['Последний доступный IP'],
			'readableBroadcastIp' => ['Широковещательный IP'],
			'maxHosts' => ['Допустимое количество узлов'],
		];
	}
	
	public function getPlace()
	{
		if (!is_object($this->netDomain)) return null;
		return $this->netDomain->place;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetVlan()
	{
		return $this->hasOne(NetVlans::className(), ['id' => 'vlan_id']);
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
		return $this->text_addr. ($this->name?('('.$this->name.')'):'');
	}
	
	
	/**
	 * читаемый DHCP
	 * @return string
	 */
	public function getReadableDhcp()
	{
		return empty($this->dhcp)?'':long2ip($this->dhcp);
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
	private function IPv4Block()
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

	public function getMaxHosts()
	{
		return max(1,$this->capacity-2);
	}
	
	/**
	 * Network
	 * @return \yii\db\ActiveQuery|Networks
	 */
	public function getIps()
	{
		return $this->hasMany(NetIps::className(), ['networks_id'=>'id'])->orderBy(['addr'=>SORT_ASC]);
	}
	
	/**
	 * @return \yii\db\ActiveQuery|Segments
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::className(), ['id' => 'segments_id']);
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
	
	
	public function fetchIp($i)
	{
		foreach ($this->ips as $ip) if ($ip->addr == $this->addr+$i) return $ip;
		return null;
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
			
			if (!empty($this->text_dhcp)) $this->dhcp=ip2long($this->text_dhcp);
	
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
