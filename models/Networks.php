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
 * @property int $vlan_id
 * @property int $addr
 * @property int $mask
 * @property int $capacity
 * @property int $used
 * @property string $readableRouter
 * @property int $router
 * @property int $usedPercent
 * @property string $readableDhcp
 * @property int $dhcp
 * @property string $comment
 * @property NetVlans $netVlan
 * @property NetDomains $netDomain
 * @property NetIps[] $ips
 */
class Networks extends \yii\db\ActiveRecord
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
            [['vlan_id', 'addr', 'mask', 'router', 'dhcp'], 'integer'],
            [['name','domain'], 'string', 'max' => 255],
			[['comment'], 'safe'],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'name' => 'Название сети',
			'vlan_id' => 'Vlan',
			'netDomain' => 'L2 Домен',
			'addr' => 'Адрес',
			'text_addr' => 'Адрес и маска',
			'mask' => 'Маска',
			'router' => 'Шлюз',
			'text_router' => 'Шлюз',
			'readableRouter' => 'Шлюз',
			'dhcp' => 'DHCP сервер',
			'readableDhcp' => 'DHCP сервер',
			'text_dhcp' => 'DHCP сервер',
			'comment' => 'Пояснение',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'name' => 'Короткое понятное название',
			'vlan_id' => 'К какому Vlan относится эта сеть',
			'text_addr' => 'Адрес и маска сети (в десятичной нотации 192.168.1.0/24)',
			'addr' => 'Адрес сети (в понятной нотации 192.168.0.0)',
			'mask' => 'Маска сети (в понятной нотации 255.255.255.0)',
			'router' => 'Кто является шлюзом в сети (опционально)',
			'text_router' => 'Кто является шлюзом в сети (опционально)',
			'dhcp' => 'Кто является DHCP сервером (опционально)',
			'text_dhcp' => 'Кто является DHCP сервером (опционально)',
			'comment' => 'Все что нужно знать про сеть сверх того, что уже внесено выше',
		];
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
		return $this->text_addr.'('.$this->name.')';
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
		return $this->capacity-2;
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
	public function getIps()
	{
		if (!is_null($this->ips_cache)) return $this->ips_cache;
		
		/*return $this->hasMany(NetIps::className(), []) ->where(['AND',
			['>=','addr',$this->addr],
			['<','addr',$this->addr+$this->capacity]
		]);*/
		return $this->ips_cache=NetIps::find()->where(['AND',
			['>=','addr',$this->addr],
			['<','addr',$this->addr+$this->capacity]
			])->all();
		
		/*
		 * Я Извиняюсь перед самим собой за эту дичь ниже.
		 * короче смысл в том что мы бинарно сдвигаем влево (делим на два) и адрес сети и адрес IP
		 * столько раз, сколько у нас нулей в маске сети. В итоге мы сравниваем только ту часть числа где в маске единички
		 * Если получились одинаковые числа - значит адрес находится в искомой подсети
		 * Украл я это все отсель: https://stackoverflow.com/questions/10001933/check-if-ip-is-in-subnet
		 * Почему 33, а не 32 - я не понял, но работает
		 */
		/*return $this->ips_cache=NetIps::findAll(
			new Expression(
				"((-1 << (32-:netmask)) & :netaddr) = ((-1 << (32-:netmask)) & `net_ips`.`addr`)",
				[
					':netmask'=>$this->mask,
					':netaddr'=>$this->addr,
				]
			)
		);*/
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
	
	
	/** Возвращает список всех элементов
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
