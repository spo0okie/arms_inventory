<?php

namespace app\models;

use Yii;
use PhpIP;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Expression;

/**
 * This is the model class for table "net_ips".
 *
 * @property int $id
 * @property int $addr
 * @property int $mask
 * @property int $networks_id
 * @property string $text_addr
 * @property string $name
 * @property string $sname
 * @property string $comment
 * @property Comps[] $comps
 * @property Techs[] $techs
 * @property Networks $network
 */
class NetIps extends \yii\db\ActiveRecord
{

	private $network_cache=null;
	
	public static $title='Сетевой адрес';
	public static $titles='Сетевые адреса';
	
	/**
	 * @var PhpIP\IPv4Block
	 */
	public static $loopbackNet=null;
	
	/**
	 * @var PhpIP\IPv4Block
	 */
	public static $apipaNet=null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'net_ips';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['addr', 'mask','networks_id'], 'integer'],
            [['text_addr'], 'ip', 'ipv6' => false, 'subnet'=>null],
			[['comment','name'],'string']
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'addr' => 'Адрес',
			'mask' => 'Маска',
			'name' => 'Имя',
			'network' => 'Сеть',
			'attached' => 'Прикреплено к',
			'text_addr' => 'Адрес',
			'comment' => 'Комментарий',
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'addr' => 'Адрес',
			'mask' => 'Маска',
			'name' => 'Сюда можно записать имя узла для которого адрес зарезервирован',
			'text_addr' => 'Также можно указать маску',
			'comment' => 'Комментировать то можно что угодно. Чем IP адрес плох',
		];
	}
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->text_addr.(empty($this->name)?'':(' ('.$this->name.')'));
	}
	
	/**
	 * Network
	 * @return Networks
	 */
	public function getNetwork()
	{
		return static::hasOne(Networks::className(), ['id' => 'networks_id']);
	}
	
	/**
	 * Network
	 * @return Networks
	 */
	public function findNetwork()
	{
		return	Networks::find()
			->where(
				['AND',
					['<=','addr',$this->addr],
					['>','addr + Power(2,32-mask)',$this->addr]
				]
			)
			->orderBy(['networks.mask'=>SORT_DESC])
			->one();
	}
	
	
	public function getComps()
	{
		return static::hasMany(Comps::className(), ['id' => 'comps_id'])->from(['ip_comps'=>Comps::tableName()])
			->viaTable('{{%ips_in_comps}}', ['ips_id' => 'id']);
	}
	
	public function getTechs()
	{
		return static::hasMany(Techs::className(), ['id' => 'techs_id'])->from(['ip_techs'=>Techs::tableName()])
			->viaTable('{{%ips_in_techs}}', ['ips_id' => 'id']);
	}
	
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (strpos($this->text_addr,'/')!==false) {
				$tokens=explode('/',$this->text_addr);
				$addr=$tokens[0];
				$mask=$tokens[1];
			} else {
				$addr=$this->text_addr;
				$mask=null;
			}
			$ip4=new PhpIP\IPv4($addr);
			$this->addr=$ip4->numeric();
			$this->mask=$mask;
			$this->text_addr=$ip4->humanReadable();
			if (!is_null($mask)) $this->text_addr.='/'.$mask;
			
			if (is_object($network=$this->findNetwork()))
				$this->networks_id=$network->id;
			else
				$this->networks_id=null;

			return true;
			
		}
		return false;
	}
	
	/**
	 * Ищет ID по адресу. если не находит и можно создать, то создает
	 * @param              $addr
	 * @param bool|integer $mask
	 * @param bool         $create
	 * @return int|null
	 */
	public static function fetchByAddr($addr,$mask=null,$create=true) {
		//если не находим такого адреса
		if (is_null($item=static::findOne(['addr'=>$addr]))) {
			if ($create) {
				$item=new NetIps();
				$item->text_addr=long2ip($addr);
				if (!is_null($mask)) $item->text_addr.='/'.$mask;
				$item->save();
			} else return null;
		}
		return $item->id;
	}
	
	/**
	 * Ищет ID по адресу. если не находит и можно создать, то создает
	 * @param              $addr
	 * @param bool|integer $mask
	 * @param bool         $create
	 * @return int|null
	 */
	public static function fetchByTextAddr($addr,$create=true) {
		//если не находим такого адреса
		if (is_null(
			$item=static::findOne([
				'addr'=>ip2long(static::removeMask($addr))
			])
		)) {
			if ($create) {
				$item=new NetIps();
				$item->text_addr=$addr;
				$item->save();
			} else return null;
		}
		return $item->id;
	}
	
	public static function removeMask($text_addr)
	{
		if ($slash=strpos($text_addr,'/')) {
			return substr($text_addr,0,$slash);
		}
		return $text_addr;
	}
	
	/**
	 * Отфильтровываем ненужные адреса из диапазонов
	 * loopback - 127.0.0.0/8
	 * APIPA - 169.254.0.0/16
	 * А также ошибочные
	 * @param string $text_addr
	 * @return bool
	 */
	public static function filterLocal($text_addr) {
		if (!strlen(trim($text_addr))) return false;
		//Если сети loopback & apipa не инициированы - инициализируем
		if (is_null(static::$loopbackNet)) static::$loopbackNet=new PhpIP\IPv4Block('127.0.0.0/8');
		if (is_null(static::$apipaNet)) static::$apipaNet=new PhpIP\IPv4Block('169.254.0.0/16');
		
		//проверяем вхождение
		try {
			$ip=new PhpIP\IPv4(static::removeMask($text_addr));
		} catch (Exception $e) {
			return false;
		}
		
		if (static::$loopbackNet->containsIP($ip)) return false;
		if (static::$apipaNet->containsIP($ip)) return false;
		return true;
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
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
}