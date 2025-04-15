<?php

namespace app\models;

use Adldap\Models\Computer;
use app\helpers\ArrayHelper;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "soft".
 *
 * @property int $id Идентификатор
 * @property int $manufacturers_id Разработчик
 * @property string $descr Описание
 * @property string $fullDescr Описание с производителем
 * @property string $comment Комментарий
 * @property string $items Основные элементы входящие в пакет ПО
 * @property string $additional Дополнительные элементы входящие в пакет ПО
 * @property string $created_at Время создания
 * @property array $softLists Массив объектов списков ПО, в которые включено ПО
 * @property array $softLists_ids Массив ID списков ПО, в которые включено ПО
 * @property array $comps_ids Массив ID компов, на которые закреплено ПО
 * @property array $hits_ids Массив ID компов, на которые установлено ПО
 * @property bool $isFree входит в список бесплатного ПО
 * @property bool $isIgnored входит в список игнорируемого ПО
 *
 * @property Soft[] $comps Массив объектов компов, на которые ПО внесено в паспорт
 * @property Soft[] $hits Массив объектов компов, на которые установлено ПО
 * @property-read LicGroups[] $licGroups
 * @property-read ActiveQuery $licItems
 * @property Manufacturers $manufacturer
 */
class Soft extends ArmsModel
{
	public static $title='ПО';
	public static $titles='ПО';
	
	private static $all_items=null;
	private static $comps_in_soft=null;
	public static $disable_cache=false;
	public static $disable_rescan=false;
	private $doNotRescan=false;
	public $add_item='';
	
	/** @inheritdoc  */
	protected static $syncableFields=[
		'descr',
		'comment',
		'items',
		'additional',
		'updated_at',
		'updated_by',
		'links',
	];
	
	/** @inheritdoc */
	public static $syncableDirectLinks=['manufacturers_id'=>'Manufacturers'];
	
	/**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'soft';
    }
	
    public function extraFields()
	{
		return['name'];
	}
	
	public $linksSchema=[
		'softLists_ids'=>[SoftLists::class, 'soft_ids'],
		'comps_ids'=>[Computer::class, 'soft_ids'],
		'hits_ids'=>[Computer::class, 'softHits_ids'],
		'licGroups_ids'=>[LicGroups::class, 'soft_ids'],
	];
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['softLists_ids','comps_ids'], 'each', 'rule'=>['integer']],
            [['manufacturers_id', 'descr'], 'required'],
            [['manufacturers_id'], 'integer'],
            [['items','additional'], 'string'],
            [['created_at','add_item','links'], 'safe'],
            [['descr', 'comment'], 'string', 'max' => 255],
			[['descr', 'manufacturers_id'], 'unique', 'targetAttribute' => ['descr', 'manufacturers_id']],
            [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::class, 'targetAttribute' => ['manufacturers_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return ArrayHelper::merge(parent::attributeData(),[
            'id' => 'Идентификатор',
            'manufacturers_id' => 'Разработчик',
            'descr' => 'Наименование',
            'comment' => 'Описание ПО',
            'items' => 'Основные элементы входящие в пакет ПО',
            'additional' => 'Дополнительные элементы входящие в пакет ПО',
	        'softLists_ids' => 'В списках ПО',
	        'created_at' => 'Дата добавления',
			'hitsCount'=>[
				'Обнаружено',
				'indexHint'=>'Количество обнаруженных установок продукта',
			],
			'compsCount'=>[
				'Паспортов',
				'indexHint'=>'Количество внесений продукта в паспорта АРМ'
			],
			'licGroupsCount'=>[
				'Типов лицензий',
				'indexHint'=>'Количество типов лицензий, включающих данное ПО',
			],
			'licCount'=>[
				'Лицензий',
				'indexHint'=>'Количество лицензий, включающих данное ПО'
			],
        ]);
    }
	
	/**
	 * Типы лицензий включающие данное ПО
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getLicGroups()
    {
		return $this->hasMany(LicGroups::class, ['id' => 'lics_id'])
			->viaTable('{{%soft_in_lics}}', ['soft_id' => 'id']);
    }
	
	/**
	 * Количество типов лицензий включающих данное ПО
	 * @return int
	 */
	public function getLicGroupsCount() {return count($this->licGroups);}
	
	/**
	 * Закупки лицензий этого ПО
	 * @return ActiveQuery
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['lic_group_id' => 'id'])
			->via('licGroups');
	}
	
	/**
	 * Количество лицензий данного ПО
	 * @return integer
	 */
	public function getLicCount() {
    	$count = 0;
    	foreach ($this->licItems as $item) {
        	$count += $item->count;
    	}
    	return $count;
	}

    /**
     * @return ActiveQuery
     */
    public function getManufacturer()
    {
        return $this->hasOne(Manufacturers::class, ['id' => 'manufacturers_id']);
    }

    /**
     * @return string
     */
    public function getFullDescr()
    {
        return $this->manufacturer->name.' '.$this->descr;
    }


    /**
     * Возвращает набор списков, в которых находится ПО
     */
    public function getSoftLists()
    {
        return $this->hasMany(SoftLists::class, ['id' => 'list_id'])
            ->viaTable('{{%soft_in_lists}}', ['soft_id' => 'id']);
    }
	
	/**
	 * Возвращает набор компов, в которых закреплено ПО
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comp_id'])
			->viaTable('{{%soft_in_comps}}', ['soft_id' => 'id']);
	}
	
	public function getCompsCount() {
		if (static::compsInSoftCached()) {
			return count(ArrayHelper::getItemsByFields(static::$comps_in_soft,['soft_id'=>$this->id]));
		}
		return count($this->comps);
	}
	
	
	/**
	 * Возвращает набор компов, в которых находится ПО
	 */
	public function getHits()
	{
		if (SoftHits::allItemsLoaded()) {
			return ArrayHelper::getItemsByFields(SoftHits::getAllItems(),['soft_id'=>$this->id]);
		}
		
		return $this->hasMany(Comps::class, ['id' => 'comp_id'])
			->viaTable('{{%soft_hits}}', ['soft_id' => 'id']);
	}
	
	public function getHitsCount() {return count($this->hits);}
	
	public function getName()
	{
		return	is_object($this->manufacturer)?$this->manufacturer->name.' '.$this->descr:$this->descr;
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		$scans=Scans::find()->where(['soft_id' => $this->id ])->all();
		$scans_sorted=[];
		foreach ($scans as $scan) if($scan->id == $this->scans_id) $scans_sorted[]=$scan;
		foreach ($scans as $scan) if($scan->id != $this->scans_id) $scans_sorted[]=$scan;
		return $scans_sorted;
	}
	
	/**
     * Возвращает количество совпадения с переданными массивами входных строк
     * @param array $strings входные строки среди которых искать продукт
     * @param array $additional дополнительные строки среди которых искать дополнительные продукты, если есть совпадения в основных
     * @return SoftHits
     */
    public function findHits(array $strings,$additional=[]) {
		return new SoftHits([
			'masks'=>$this->getItemsArray(),
			'additional_masks'=>$this->getAdditionalArray(),
			'strings'=>$strings,
			'additional_strings'=>$additional,
		]);
    }

	/**
     * Возвращает набор строк-дополнительных элементов ПО
     */
    public function getAdditionalArray()
    {
        $arrItems = explode("\n",$this->additional);
        foreach ($arrItems as $i => $item) {
            $arrItems[$i]=trim($item);
        }
        return $arrItems;
    }

    /**
     * Возвращает набор строк-элементов ПО
     */
    public function getItemsArray()
    {
        $arrItems = explode("\n",$this->items);
        foreach ($arrItems as $i => $item) {
            $arrItems[$i]=trim($item);
        }
        return $arrItems;
    }

	public static function fetchItemsArray($id)
	{
		$arrItems = explode("\n",static::fetchItem($id)->items);
		foreach ($arrItems as $i => $item) {
			$arrItems[$i]=trim($item);
		}
		return $arrItems;
	}
	
	/**
	 * Возвращает набор строк-дополнительных элементов ПО
	 * @param int $id
	 * @return false|string[]
	 */
	public static function fetchAdditionalArray(int $id)
	{
		$arrItems = explode("\n",static::fetchItem($id)->additional);
		foreach ($arrItems as $i => $item) {
			$arrItems[$i]=trim($item);
		}
		return $arrItems;
	}

    public function addItem($item)
    {
        if (!mb_strlen($item)) return $this->items;
        return $this->items=implode("\n",array_merge($this->getItemsArray(),[$item]));
    }

    public function subItem($item)
    {
        if (!mb_strlen($item)) return $this->items;
        return $this->items=implode("\n",array_diff($this->getItemsArray(),[$item]));
    }

    /**
     * Возвращает флаг наличия ПО в списке игнорируемого ПО
     */
    public function getIsIgnored()
    {
		return (array_search(SoftLists::getIgnoredListId(),SoftLists::getSoftLists($this->id),false)!==false);
        //return (array_search(SoftLists::getIgnoredListId(),$this->softLists_ids,false)!==false);
    }

    /**
     * Возвращает флаг наличия ПО в списке согласованного ПО
     */
    public function getIsAgreed()
    {
	    return (array_search(SoftLists::getAgreedListId(),SoftLists::getSoftLists($this->id),false)!==false);
	    //return (array_search(SoftLists::getAgreedListId(),$this->softLists_ids,false)!==false);
    }

	/**
	 * Возвращает флаг наличия ПО в списке согласованного ПО
	 */
	public function getIsFree()
	{
		return (array_search(SoftLists::getFreeListId(),SoftLists::getSoftLists($this->id),false)!==false);
		//return (array_search(SoftLists::getFreeListId(),$this->softLists_ids,false)!==false);
	}


	/**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array|null $items список элементов для вывода
     * @param string $keyField поле - ключ
     * @param string $valueField поле - значение
     * @param bool $asArray
     * @return array
     */
    public static function listItems($items=null, $keyField = 'id', $valueField = 'descr', $asArray = true)
    {

        $query = static::find();
        if (!is_null($items)) $query->filterWhere(['id'=>$items]);
        if ($asArray) $query->select([$keyField, $valueField])->asArray();

        return \yii\helpers\ArrayHelper::map($query->all(), $keyField, $valueField);
    }


    /**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array|null $items список элементов для вывода
     * @return array
     */
    public static function listItemsWithPublisher($items=null)
    {

        $query = static::find()->select(['soft.id', 'CONCAT(`manufacturers`.`name`, \' \', `soft`.`descr`) as `fullDescr`'])->asArray();
        if (!is_null($items)) $query->filterWhere(['soft.id'=>$items]);
        $query->leftJoin('manufacturers','`soft`.`manufacturers_id` = `manufacturers`.`id`');
        //var_dump($query->all());
        //return $query->all();
	    $list=\yii\helpers\ArrayHelper::map($query->all(), 'id', 'fullDescr');
	    asort ($list);
        return $list;
    }
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		//error_log('saving');
		if (parent::beforeSave($insert)) {
			if (is_object($this->manufacturer)) {
				//если есть производитель, то его название надо бы убрать из имени софта
				$this->descr=$this->manufacturer->cropVendorName($this->descr);
			}
			
			$this->items=implode("\n",StringHelper::explode($this->items,"\n",true,true));
			$this->additional=implode("\n",StringHelper::explode($this->additional,"\n",true,true));
			return true;
		}
		return false;
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		if (static::$disable_rescan || $this->doNotRescan) return;
		
		if (//если поменялся состав
			array_key_exists('items',$changedAttributes)
			||//или дополнительный составл
			array_key_exists('additional',$changedAttributes)
			||//или это новый элемент
			$insert
		) {
			$items=ArrayHelper::explode("\n",$this->items);
			if (!count($items)) return;
			
			if (count($items)==1)
				$where=['regexp','raw_soft',$items[0]];
			else {
				$where=['or'];
				foreach ($items as $item)
					$where[]=['regexp','raw_soft',$item];
			}
			/** @var Comps $comps */
			$comps=Comps::find()
				->where($where)
				->all();
			if (Yii::$app->params['soft.deferred_rescan'])	{
				//отложенный рескан
				foreach ($comps as $comp) {
					$queue=new CompsRescanQueue([
						'soft_id'=>$this->id,
						'comps_id'=>$comp->id
					]);
					$queue->save();
				}
			} else {
				//моментальный
				foreach ($comps as $comp) $comp->silentSave();
			}
		}
	}
	
	public static function fetchAll(){
		if (static::$disable_cache) return static::find()->all();
		if (is_null(static::$all_items)) {
			/** @var Soft[] $tmp */
			$tmp=static::find()->all();
			static::$all_items=[];
			foreach ($tmp as $item) static::$all_items[$item->id]=$item;
		}
		return static::$all_items;
	}
	
	public static function fetchItem($id){
		if (static::$disable_cache) return static::findOne($id);
		return static::fetchAll()[$id]??null;
	}
	
	public static function fetchItems($ids){
		$tmp=[];
		foreach ($ids as $id) $tmp[$id]=static::fetchItem($id);
		return $tmp;
	}
	
	public static function fetchBy($condition){
		foreach ($condition as $param=>$values) if (!is_array($values)) $condition[$param]=[$values];
		$tmp=[];
		foreach (static::fetchAll() as $item) {
			$match=true;
			foreach ($condition as $param=>$values) {
				if (!in_array($item->$param,$values)) $match=false;
			}
			if ($match) $tmp[]=$item;
		}
		return $tmp;
	}
	
	
	public static function compsInSoftCached() {
		return !is_null(static::$comps_in_soft);
	}
	public static function cacheCompsInSoft() {
		static::$comps_in_soft=(new Query())
			->select('*')
			->from('soft_in_comps')
			->all();
	}
	
	/**
	 * @inheritDoc
	 */
	public static function syncFindLocal($name) {
		return static::find()
			->joinWith('manufacturer')
			->where(['LOWER(CONCAT(manufacturers.name,\' \',soft.descr))'=>mb_strtolower($name)])
			->all();
	}
	
	public function beforeDelete()
	{
		$this->comps_ids=[];
		$this->hits_ids=[];
		$this->doNotRescan=true;
		$this->silentSave(false);
		return parent::beforeDelete();
	}
	
}
