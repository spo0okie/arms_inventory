<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "manufacturers".
 *
 * @property int $id Идентификатор
 * @property string $name Название
 * @property string $full_name Полное название
 * @property string $comment Комментарий
 * @property string $created_at Время создания
 *
 * @property ManufacturersDict[] $manufacturersDicts
 * @property ManufacturersDict[] $dict
 * @property Soft[] $soft
 * @property TechModels[] $techModels
 */
class Manufacturers extends ArmsModel
{

	private $all_names=[];

    public static $CACHE_TIME=15;

    private static $all_items=null;
    private static $names_cache=null;

    /** @inheritdoc   */
    protected static $syncableFields=[
		'name',
    	'updated_at',
		'updated_by',
		'full_name',
		'comment'
	];

	/** @inheritdoc   */
    public static $syncableReverseLinks=[
    	'ManufacturersDict'=>'manufacturers_id'
	];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'manufacturers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['created_at'], 'safe'],
            [['name', 'full_name', 'comment'], 'string', 'max' => 255],
            [['name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор',
            'name' => 'Название',
            'full_name' => 'Полное название',
            'comment' => 'Комментарий',
            'created_at' => 'Время создания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManufacturersDicts()
    {
        return $this->hasMany(ManufacturersDict::className(), ['manufacturers_id' => 'id']);
    }


    public function findAllNames($name) {
	    $ck_item=mb_strtolower($name);
	    foreach ($this->all_names as $item)
		    if (strcmp($item['low'],$ck_item)==0) return true;
	    return false;
    }

	/**
	 * Добавляет одно имя в список всех синонимов
	 */
	private function addAllNames($name)
	{
		if ($this->findAllNames($name)) return;
		$this->all_names[]=[
			'name'=>$name,
			'low'=>mb_strtolower($name)
		];
	}

    private function fillAllNames(){
	    if (count($this->all_names)) return;
	    $this->addAllNames($this->name);
	    $this->addAllNames($this->full_name);
    	if (is_array($dicts=$this->manufacturersDicts) && count ($dicts)) foreach ($dicts as $dict) {
		    $this->addAllNames($dict->word);
	    }
    }

	/**
	 * возвращает количество символов слева, которое надо откусить от названия, чтобы убрать оттуда
	 * название производителя (для случаев, когда названия производителя есть и в продукте)
	 * типа Microsoft Office от Microsoft
	 * @param string $name название продукта
	 * @return integer
	 */
    public function cutManufacturer($name) {
    	$cut=0;
    	$namelen=strlen($name);
		$low=mb_strtolower($name);
	    $test='['.$low.'] ';
	    $this->fillAllNames();
	    //перебираем все элементы
		foreach ($this->all_names as $item) {
			$test.=' ('.$item['low'].')';
			//если название производителя не длиннее продукта
			if (($itemlen=strlen($item['low']))<$namelen) {
				//если продукт начинается с этой строки то откусываем не меньше чем длинна названия произв
				if (strcmp(substr($low,0,$itemlen),$item['low'])==0) $cut=max($cut,$itemlen);
			}
		}
		//return $test;
		return $cut;
    }
	
	public function getSoft() {
		return \app\models\Soft::find()->where(['manufacturers_id' => $this->id])->orderBy('descr')->all();
		//return $this->hasMany(Soft::className(), ['manufacturers_id' => 'id']);
	}
	
	public function getTechModels() {
		return \app\models\TechModels::find()->where(['manufacturers_id' => $this->id])->orderBy('name')->all();
		//return $this->hasMany(Soft::className(), ['manufacturers_id' => 'id']);
	}
	
	public function getDict() {
		return $this->hasMany(ManufacturersDict::className(), ['manufacturers_id' => 'id']);
	}
	
	public static function fetchAll(){
		if (is_null(static::$all_items)) {
			$tmp=static::find()->all();
			static::$all_items=[];
			foreach ($tmp as $item) static::$all_items[$item->id]=$item;
		}
		return static::$all_items;
	}
	
	public static function fetchItem($id){
		return isset(static::fetchAll()[$id])?
			static::fetchAll()[$id]
			:
			null;
	}
	
	public static function fetchItems($ids){
		$tmp=[];
		foreach ($ids as $id) $tmp[$id]=static::fetchItem($id);
		return $tmp;
	}
	
	public static function fetchNames()
	{
		if (!is_null(static::$names_cache)) return static::$names_cache;
		$names= ArrayHelper::map(static::fetchAll(), 'id', 'name');
		asort($names);
		return static::$names_cache=$names;
	}
	
	/**
	 * возвращает элементы, поле которые имеет значение value
	 * @param $field
	 * @param $value
	 * @return array
	 */
	public static function fetchByField($field,$value){
		$tmp=[];
		foreach (static::fetchAll() as $item)
			if ($item->$field == $value) $tmp[$item->id]=$item;
		return $tmp;
	}
	
	/**
	 * Вырезать имя вендора из названия если оно идет спереди
	 * @param $name
	 * @return bool
	 */
	public function cropVendorName($name){
		$orig=$name;
		$full=mb_strtolower($name);
		$vendor=mb_strtolower($this->name).' ';	//убираем регистр и добавляем пробел,
												//т.к. вендор должен быть отдельным словом и при этом не последним
		if (mb_strpos($full,$vendor)===0) {
			//название продукта начинается с имени производителя
			$name=trim(mb_substr($name,mb_strlen($vendor)));
		} else {
			//проверяем все синонимы написания производителя
			foreach ($this->manufacturersDicts as $dict) {
				$vendor=mb_strtolower($dict->word).' '; //убираем регистр и добавляем пробел, также как и выше
				if (mb_strpos($full,$vendor)===0) {
					$name=trim(mb_substr($name,mb_strlen($vendor)));
				}
			}
		}
		return mb_strlen($name)>3?$name:$orig;
		
	}
	
}
