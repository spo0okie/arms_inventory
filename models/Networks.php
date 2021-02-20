<?php

namespace app\models;

use PhpIP;
use Yii;




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
 * @property string $readableRouter
 * @property int $router
 * @property string $readableDhcp
 * @property int $dhcp
 * @property string $comment
 * @property NetVlans $netVlan
 */
class Networks extends \yii\db\ActiveRecord
{
	
	public static $title='Сети';
	public $text_dhcp;
	public $text_router;
	
	
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
            [['name'], 'string', 'max' => 255],
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
		return long2ip($this->dhcp);
	}
	
	
	/**
	 * читаемый DHCP
	 * @return string
	 */
	public function getReadableRouter()
	{
		return long2ip($this->router);
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
