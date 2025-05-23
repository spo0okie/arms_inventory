<?php

namespace app\models;

use yii\db\ActiveQuery;
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
	
	public $linksSchema=[
		'soft_ids'=>[Soft::class, 'manufacturers_id','loader'=>'soft'],
		'tech_models_ids'=>[TechModels::class, 'manufacturers_id'],
		'manufacturers_dicts_ids'=>[ManufacturersDict::class, 'manufacturers_id'],
	];

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
    public function attributeData()
    {
        return [
            'id' => 'Идентификатор',
            'name' => ['Название','hint'=>'Короткое обозначение производителя для отображения в списках'],
            'full_name' => ['Полное название','hint'=>'Полное название производителя для исключения совпадений'],
            'comment' => ['Комментарий','hint'=>'Дополнительная информация о производителе (кто такой, чем знаменит и т.д.)'],
            'created_at' => 'Время создания',
        ];
    }

    /**
     * @return ActiveQuery
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
	 * Возвращает количество символов слева, которое надо откусить от названия, чтобы убрать оттуда
	 * название производителя (для случаев, когда названия производителя есть и в продукте)
	 * типа Microsoft Office от Microsoft
	 * @param string $name название продукта
	 * @return integer
	 */
    public function cutManufacturer($name) {
    	$cut=0;
    	$nameLen=strlen($name);
		$low=mb_strtolower($name);
	    $test='['.$low.'] ';
	    $this->fillAllNames();
	    //перебираем все элементы
		foreach ($this->all_names as $item) {
			//$test.=' ('.$item['low'].')';
			//если название производителя не длиннее продукта
			if (($itemLen=strlen($item['low']))<$nameLen) {
				//если продукт начинается с этой строки, то откусываем не меньше чем длинна названия произв
				if (strcmp(substr($low,0,$itemLen),$item['low'])==0) $cut=max($cut,$itemLen);
			}
		}
		//return $test;
		return $cut;
    }
	
	public function getSoft() {
		//return \app\models\Soft::find()->where(['manufacturers_id' => $this->id])->orderBy('descr')->all();
		return $this->hasMany(Soft::class, ['manufacturers_id' => 'id']);
	}
	
	public function getTechModels() {
		//return \app\models\TechModels::find()->where(['manufacturers_id' => $this->id])->orderBy('name')->all();
		return $this->hasMany(Soft::class, ['manufacturers_id' => 'id']);
	}
	
	public function getDict() {
		return $this->hasMany(ManufacturersDict::class, ['manufacturers_id' => 'id']);
	}
	
	/**
	 * @return Manufacturers[]
	 */
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
	 * Возвращает элементы, поле которые имеет значение value
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
		$vendor=mb_strtolower($this->name).' ';	//Убираем регистр и добавляем пробел,
												//так как вендор должен быть отдельным словом и при этом не последним
		if (mb_strpos($full,$vendor)===0) {
			//название продукта начинается с имени производителя
			$name=trim(mb_substr($name,mb_strlen($vendor)));
		} else {
			//проверяем все синонимы написания производителя
			foreach ($this->manufacturersDicts as $dict) {
				$vendor=mb_strtolower($dict->word).' '; //убираем регистр и добавляем пробел, так же как и выше
				if (mb_strpos($full,$vendor)===0) {
					$name=trim(mb_substr($name,mb_strlen($vendor)));
				}
			}
		}
		return mb_strlen($name)>3?$name:$orig;
	}
	
}
