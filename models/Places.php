<?php

namespace app\models;

use voskobovich\linker\LinkerBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "places".
 *
 * @property string      $id id
 * @property integer     $parent_id
 * @property string      $name Название
 * @property string      $fullName Полное название
 * @property string      $prefTree Префикс с резервированием родительским.
 * @property string      $addr Адрес
 * @property Techs       $techsRecursive
 * @property Places      $top помещение самого верхнего уровня над текущим
 * @property Places      $parent родительское помещения
 * @property Techs       $techs техника размещенная в этом помещении
 * @property string      $prefix Префикс
 * @property string      $short Короткое имя
 *
 * @property OrgInet[]   $inets
 * @property OrgPhones[] $phones
 * @property OrgPhones[] $phonesRecursive
 * @property Places[]    $children
 * @property Materials[] $materials
 */
class Places extends ArmsModel
{


	/*
	для рекурсивного запроса полного пути помещения в БД были добавлены хранимая процедура и функция ее вызывающая
	уперто отсюда: https://stackoverflow.com/questions/20215744/how-to-create-a-mysql-hierarchical-recursive-query

DROP PROCEDURE IF EXISTS getplacepath;
DELIMITER $$
CREATE PROCEDURE getplacepath(IN place_id INT, OUT path TEXT)
BEGIN
    DECLARE placename VARCHAR(20);
    DECLARE temppath TEXT;
    DECLARE tempparent INT;
    SET max_sp_recursion_depth = 32;
    SELECT short, parent_id FROM places WHERE id=place_id INTO placename, tempparent;
    IF tempparent IS NULL
    THEN
        SET path = placename;
    ELSE
        CALL getplacepath(tempparent, temppath);
        SET path = CONCAT(temppath, '/', placename);
    END IF;
END$$
DELIMITER ;


DROP FUNCTION IF EXISTS getplacepath;
DELIMITER $$
CREATE FUNCTION getplacepath(place_id INT) RETURNS TEXT DETERMINISTIC
BEGIN
    DECLARE res TEXT;
    CALL getplacepath(place_id, res);
    RETURN res;
END$$
DELIMITER ;

	проверено вызовом
	SELECT *,getplacepath(Id) as path FROM `places`
	работает
	 */
	
	public static $title="Помещение";
	public static $titles="Помещения";
	//public $path;

	private $phones_cache=null;
	private $children_cache=null;
	private static $all_items=null;
	
	protected static $allItems=null;
	
	/**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'places';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			[['parent_id'], 'integer'],
			[['parent_id'], function ($attribute) {
				$children=[$this->id];
				if (is_object($this->parent) && $this->parent->loopCheck($children)!==false) {
					$chain=[];
					foreach ($children as $id) {
						$child=static::findOne($id);
						$chain[]=$child->short;
					}
					//$chain[]=$this->short;
					$this->addError($attribute,'Ссылка на самого себя: '.implode(' -> ',$chain));
				}
			}],
            [['name', 'short'], 'required'],
            [['name'], 'string', 'max' => 128],
            [['addr'], 'string', 'max' => 255],
            [['prefix'], 'string', 'max' => 5],
			[['short'], 'string', 'max' => 16],
			[['comment'], 'safe'],
        ];
    }
	
	
	/**
	 * В списке поведений прикручиваем many-to-many ссылки
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'materials_ids' => 'materials',		//one-2-many
					'services_ids' => 'services',		//one-2-many
					'techs_ids' => 'techs',				//one-2-many
				]
			]
		];
	}
	
	
	/**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
	        'parent_id' => 'Родитель',
	        'parent' => 'Предок',
            'name' => 'Полное имя',
            'addr' => 'Адрес',
            'phone' => 'Телефон',
            'prefix' => 'Префикс',
            'short' => 'Короткое имя',
        ];
    }

	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		return [
			'parent_id' => 'Помещение внутри которого находится это',
			'name' => 'Понятное название помещения без сокращений',
			'short' => 'Сокращенное название помещения для вывода в узких местах',
			'addr' => 'Если не указан, то наследуется адрес родительского помещения',
			'phone' => 'Если для помещения предусмотрены прямые телефоны, укажите их здесь',
			'prefix' => 'Будет использоваться для формирования инвентарных номеров при заведении нового оборудования в этом помещении. Если не задать - используется родительский префикс. Если изменить, то старые инвентарные номера останутся неизменны.',
		];
	}
	
	/**
	 * Проверяем петлю по связи потомок-предок
	 * @param $children integer[]
	 * @return false|int
	 */
	public function loopCheck(array &$children)
	{
		//если предок уже встречается среди потомков, то сообщаем его
		if (($loop=array_search($this->id,$children))!==false) {
			$children[]=$this->id;
			return $this->id;
		}
		
		//добавляем себя в цепочку потомков
		$children[]=$this->id;

		//если родителей нет - то нет и петли
		if (empty($this->parent_id)) return false;
		
		//спрашиваем у предка
		return $this->parent->loopCheck($children);
	}

	/**
	 * @return OrgPhones|ActiveQuery
	 */
	public function getPhones()
	{
		if (!is_null($this->phones_cache)) return $this->phones_cache;
		return $this->phones_cache = $this->hasMany(OrgPhones::class, ['places_id' => 'id']);
	}


	/**
	 * @return OrgPhones[]
	 */
	public function getPhonesRecursive()
	{
		$phones=$this->phones;
		if (count($this->children)) foreach ($this->children as $child) {
			$phones=array_merge($phones,$child->phonesRecursive);
		}
		return $phones;
	}


	/**
	 * @return ActiveQuery
	 */
	public function getInets()
	{
		return $this->hasMany(OrgInet::class, ['places_id' => 'id']);
	}
	
	public function getServices()
	{
		return $this->hasMany(Services::class, ['places_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getTechs()
	{

		return $this->hasMany(Techs::class, ['places_id' => 'id'])
			->from(['places_techs'=>Techs::tableName()]);
		//	->andWhere(['is', 'places_techs.arms_id', new \yii\db\Expression('null')]);
		//	->andOnCondition(['is', 'places_techs.arms_id', new \yii\db\Expression('null')]);

	}
	
	/**
	 * @return Techs[]
	 */
	public function getTechsRecursive()
	{
		$techs=$this->techs;
		foreach ($this->children as $child) {
			$techs=array_merge($techs,$child->techs);
		}
		return $techs;
	}
	
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getMaterials()
	{
		return $this->hasMany(Materials::class, ['places_id' => 'id'])
			->from(['places_materials'=>Materials::tableName()]);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getParent()
	{
		if (static::allItemsLoaded()) {
			if (!$this->parent_id) return null;
			return static::getLoadedItem($this->parent_id);
		}
		return $this->hasOne(Places::class, ['id' => 'parent_id']);
	}
	
	/**
	 * @return Places
	 */
	public function getTop()
	{
		//return Places::find(['id'=>'getplacetop('.$this->id.')']);
		//return $this->hasOne(Places::className(), ['id' => new \yii\db\Expression('getplacetop(id)')]);
		return (is_null($this->parent_id))?$this:$this->parent->top;
	}

	/**
	 * @return Places[]
	 */
	public function getChildren()
	{
		if (!is_null($this->children_cache)) return $this->children_cache;
		$children=[];
		foreach (static::fetchAll() as $item) {
			if ($item['parent_id']===$this->id) $children[]=$item;
		}
		return $children;
		//return $this->children_cache = $this->hasMany(Places::class, ['parent_id' => 'id']);
	}

	public static function fetchAll(){
		if (!is_null(static::$all_items)) return static::$all_items;
		static::$all_items=[];
		$tmp=static::find()
			->select([
				'{{places}}.*',
				'getplacepath(id) AS path'
			])
			->orderBy('path')
			->all();
		foreach ($tmp as $item) static::$all_items[$item['id']]=$item;
		return static::$all_items;
	}


	public static function fetchItem($id){
		return isset(static::fetchAll()[$id])?
			static::fetchAll()[$id]
			:
			null;
	}

	public static function fetchNames($lev=false){
		//if (!is_null(static::$names_cache)) return static::$names_cache;
		$list= static::fetchAll();
		if ($lev===1) {
			foreach ($list as $i => $item)
				if (!is_null($item->parent_id)) unset($list[$i]);
		}
		$items= ArrayHelper::map($list, 'id', 'fullName');
		asort($items);
		return $items;
	}

	public static function fetchFullName($id){
		$item=self::fetchItem($id);
		if (!is_object($item)) return "err: Place #".$id;
		$name=$item->short;
		if ($item->parent_id) $name=self::fetchFullName($item->parent_id).'/'.$name;
		return $name;
	}

	public function getFullName(){
		if (!empty($this->path)) return $this->path;
		return self::fetchFullName($this->id);
		//if (is_object($parent=$this->parent)) $name= $parent->fullName.'/'.$name;

		//return $name;
	}

	/**
	 * Возвращает префикс, если отсутствует то префикс родителя.
	 */
	public function getPrefTree() {
		//если есть свой префикс - отдаем его
		if (strlen($this->prefix)) return $this->prefix;
		//если есть предок, то спрашиваем у него
		if ($this->parent_id) return $this->parent->prefTree;
		return '';
	}

	public function getReverseLinks() {
		return [
			$this->techs,
			$this->children,
			$this->materials,
			$this->phones,
			$this->inets,
		];
	}
}
