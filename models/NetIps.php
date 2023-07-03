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
 * @property int[] $comps_ids
 * @property int[] $techs_ids
 * @property int[] $aces_ids
 * @property Techs[] $techs
 * @property Networks $network
 * @property Segments $segment
 * @property Acls[] $acls
 * @property Aces[] $aces
 * @property Users[] $users
 * @property Places $place
 */
class NetIps extends \yii\db\ActiveRecord
{

	private $network_cache=null;
	
	public static $title='IP адрес';
	public static $titles='IP адреса';
	
	public static $inputHint='по одному в строке';
	
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
			[['text_addr'], 'unique'],
			[['comment','name'],'string']
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'comps_ids' => 'comps',
					'techs_ids' => 'techs',
					'aces_ids' => 'aces',
					'users_ids' => 'users',
				]
			]
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
	 * @return Networks|ActiveQuery
	 */
	public function getNetwork()
	{
		return $this->hasOne(Networks::className(), ['id' => 'networks_id']);
	}
	
	public function getSegment()
	{
		if (is_object($this->network)) return $this->network->segment;
	}
	
	/**
	 * Network
	 * @return Networks|array|ActiveRecord
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
	
	
	/**
	 * Возвращает привязанные ОС
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::className(), ['id' => 'comps_id'])->from(['ip_comps'=>Comps::tableName()])
			->viaTable('{{%ips_in_comps}}', ['ips_id' => 'id']);
	}
	
	/**
	 * Возвращает привязанные ОС
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::className(), ['id' => 'users_id'])->from(['ip_users'=>Users::tableName()])
			->viaTable('{{%ips_in_users}}', ['ips_id' => 'id']);
	}
	
	/**
	 * Возвращает привязанное оборудование
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::className(), ['id' => 'techs_id'])->from(['ip_techs'=>Techs::tableName()])
			->viaTable('{{%ips_in_techs}}', ['ips_id' => 'id']);
	}
	
	/**
	 * Возвращает привязанное оборудование
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::className(), ['id' => 'aces_id'])->from(['ip_aces'=>Aces::tableName()])
			->viaTable('{{%ips_in_aces}}', ['ips_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::className(), ['ips_id' => 'id']);
	}
	
	public function getPlace()
	{
		if (!is_object($this->network)) return null;
		return $this->network->place;
	}
	
	
	/**
	 * Отвязать ОС от этого IP и удалить IP если к нему более ничего не привязано
	 * @param $comp_id
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function detachComp($comp_id)
	{
		//если есть привязанные компы
		if (is_array($comps=$this->comps_ids)) {
			//если среди привязанных компов есть нужный
			if (($key = array_search($comp_id, $comps)) !== false) {
				//отрываем комп
				unset($comps[$key]);
				$this->comps_ids=$comps;
				//сохраняем изменения
				$this->save();
			}
		}
		
		$this->deleteIfEmpty();
	}
	
	/**
	 * Отвязать ОС от этого IP и удалить IP если к нему более ничего не привязано
	 * @param $tech_id
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function detachTech($tech_id)
	{
		//если есть привязанные компы
		if (is_array($techs=$this->techs_ids)) {
			//если среди привязанных компов есть нужный
			if (($key = array_search($tech_id, $techs)) !== false) {
				//отрываем комп
				unset($techs[$key]);
				$this->techs_ids=$techs;
				//сохраняем изменения
				$this->save();
			}
		}
		
		$this->deleteIfEmpty();
	}
	
	/**
	 * Отвязать ОС от этого IP и удалить IP если к нему более ничего не привязано
	 * @param $ace_id
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function detachAce($ace_id)
	{
		//если есть привязанные ACE
		if (is_array($aces=$this->aces_ids)) {
			//если среди привязанных ACE есть нужный
			if (($key = array_search($ace_id, $aces)) !== false) {
				//отрываем ACE
				unset($aces[$key]);
				$this->techs_ids=$aces;
				//сохраняем изменения
				$this->save();
			}
		}
		
		$this->deleteIfEmpty();
	}
	
	/**
	 * Удаляет IP если к нему ничего не привязано и нет комментария
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function deleteIfEmpty()
	{
		//если к IP ничего не привязано
		if (
			(
				!is_array($this->comps)	//у него нет привязанных компов
				||						//или
				count($this->comps)==0	//привязан только один
			) && (						//и
				!is_array($this->techs)	//у него нет привязанного оборудования
				||						//или
				count($this->techs)==0	//привязано 0
			) && (						//и
				!is_array($this->aces)	//у него нет привязанных ACEs
				||						//или
				count($this->aces)==0	//привязано 0
			) && (						//и
				!is_array($this->acls)	//у него нет привязанных ACLs
				||						//или
				count($this->acls)==0	//привязано 0
			) && (						//и
				empty($this->name)		//имени нет
			) && (						//и
				empty($this->comment)	//комментария нет
			)
		) {
			//можно удалять этот IP
			$this->delete();
		}
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
				$item->addr=$addr;
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
	
	
	/**
	 * Имея текстовый список IP возвращает ids объектов IP адресов
	 * @param $text
	 * @return int[]
	 */
	public static function fetchIpIds($text) {
		if (!count($items=explode("\n",$text))) return[];
		$ids=[];
		foreach ($items as $item) {
			$item=trim($item);
			if (strlen($item)) $ids[]=NetIps::fetchByTextAddr($item);
		}
		return $ids;
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
	 * Фильтрует ввод "список IP по одному в строку"
	 * удаляет некорректные значения
	 * @param $value
	 */
	public static function filterInput($value) {
		if (count($items=explode("\n",$value))) {
			$validator = new \yii\validators\IpValidator();
			$validator->subnet = null;
			$validator->ipv6 = false;
			$error=null;
			$newValue=[];
			foreach ($items as $item) if (
				$validator->validate(trim($item), $error)
				&&
				NetIps::filterLocal(trim($item))
			) $newValue[]=trim($item);
			return implode("\n",$newValue);
		}
		return '';
	}
	
	public static function validateInput(&$model,$attribute) {
		$items=explode("\n",$model->$attribute);
		$ipValidator = new \yii\validators\IpValidator(['ipv6'=>false,'subnet'=>null]);
		$error=null;
		foreach ($items as $item) if (strlen(trim($item))) {
			if (!$ipValidator->validate(trim($item), $error)) {
				$model->addError($attribute, $error.' : '.$item);
				//return; // stop on first error
			}
		}
	}
	
	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['addr'=>SORT_ASC])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
}