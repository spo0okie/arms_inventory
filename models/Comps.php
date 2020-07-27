<?php

namespace app\models;

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
 * @property string $ip IP адреса через перенос строки
 * @property string $ip_ignore Игнорировать IP адреса
 * @property int $arm_id Рабочее место
 * @property int $user_id Пользователь
 * @property string $comment Комментарий
 * @property string $updated_at Время обновления
 * @property boolean $isIgnored Софт находится в списке игнорируемого ПО
 * @property array $soft_ids Массив ID ПО, которое установлено на компе
 * @property array $comps Массив объектов ПО, которое установлено на компе
 
 * @property Arms $arm
 * @property Users $user
 * @property Domains $domain
 * @property string $updatedRenderClass
 * @property string $domainName
 * @property string $currentIp
 * @property string[] $ips
 * @property string[] $ignoredIps
 * @property string[] $filteredIps
 * @property \app\models\LoginJournal[] $lastThreeLogins
 * @property \app\models\HwList $hwList
 * @property \app\models\SwList $swList
 * @property \app\models\Services $services
 */
class Comps extends \yii\db\ActiveRecord
{

	public static $title='Операционные системы';
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['soft_ids'], 'each', 'rule'=>['integer']],
            [['name', 'os'], 'required'],
            [['domain_id', 'arm_id', 'ignore_hw', 'user_id'], 'integer'],
            [['raw_hw', 'raw_soft','exclude_hw','raw_version'], 'string'],
            [['updated_at'], 'safe'],
            [['name','raw_version'], 'string', 'max' => 32],
            [['os', 'comment'], 'string', 'max' => 128],
	        [['ip', 'ip_ignore'], 'string', 'max' => 255],
            [['domain_id', 'name'], 'unique', 'targetAttribute' => ['domain_id', 'name']],
			[['arm_id'], 'exist', 'skipOnError' => true, 'targetClass' => Arms::className(), 'targetAttribute' => ['arm_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::className(), 'targetAttribute' => ['domain_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
	        'id' => 'Идентификатор',
	        'ip' => 'IP Адрес',
			'domain_id' => 'Домен',
			'user_id' => 'Пользователь',
			'user' => 'Пользователь',
            'name' => 'Имя компьютера',
            'os' => 'Отпечаток версии ОС (заполняется скриптом)',
            'raw_hw' => 'Отпечаток железа (заполняется скриптом)',
	        'raw_soft' => 'Отпечаток софта (заполняется скриптом)',
	        'raw_version' => 'Скрипт',
            'exclude_hw' => 'Скрытое из паспорта железо',
            'ignore_hw' => 'Игнорировать аппаратное обеспечение',
            'arm_id' => 'Рабочее место',
            'comment' => 'Комментарий',
            'updated_at' => 'Время обновления',
        ];
    }


    public function behaviors()
    {
        return [
            [
                'class' => \voskobovich\linker\LinkerBehavior::className(),
                'relations' => [
                    'soft_ids' => 'soft',
                ]
            ]
        ];
    }
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArm()
	{
		return $this->hasOne(Arms::className(), ['id' => 'arm_id']);
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
		return static::getDb()->cache(function($db) {return static::hasMany(Soft::className(), ['id' => 'soft_id'])
			->viaTable('{{%soft_in_comps}}', ['comp_id' => 'id']);},Manufacturers::$CACHE_TIME);
	}

	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getServices()
	{
		return static::hasMany(Services::className(), ['id' => 'services_id'])
			->viaTable('{{%comps_in_services}}', ['comps_id' => 'id']);
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

	//список адресов, которые вернул скрипт инвентаризации
	public function getIps() {
		if (!is_null($this->ip_cache)) return $this->ip_cache;
		$this->ip_cache=explode("\n",$this->ip);
		foreach ($this->ip_cache as $i=>$ip) $this->ip_cache[$i]=trim($ip);
		$this->ip_cache=array_unique($this->ip_cache);
		return $this->ip_cache;
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
	
	public function getUpdatedRenderClass() {
		if (strlen($this->updated_at)) {
			$data_age=time()-strtotime($this->updated_at);
			if ($data_age < 3600) return 'hour_fresh';
			elseif ($data_age < 3600*24) return 'day_fresh';
			elseif ($data_age < 3600*24*7) return 'week_fresh';
			elseif ($data_age < 3600*24*30) return 'month_fresh';
			else return 'over_month_fresh';
		} else return '';
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	

}
