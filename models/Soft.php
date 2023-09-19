<?php

namespace app\models;

use app\helpers\ArrayHelper;
use Yii;
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
 * @property LicGroups[] $licGroups
 * @property Manufacturers $manufacturer
 */
class Soft extends ArmsModel
{

    private static $all_items=null;
	public static $disable_cache=false;
	public static $disable_rescan=false;
	private $doNotRescan=false;
	
	/** @inheritdoc  */
	protected static $syncableFields=[
		'descr',
		'comment',
		'items',
		'additional',
		'updated_at',
		'updated_by',
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
            [['created_at'], 'safe'],
            [['descr', 'comment'], 'string', 'max' => 255],
            [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::className(), 'targetAttribute' => ['manufacturers_id' => 'id']],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => \voskobovich\linker\LinkerBehavior::className(),
                'relations' => [
                    'softLists_ids' => 'softLists',
					'licGroups_ids' => 'licGroups',
					'comps_ids' => 'comps',
					'hits_ids' => 'hits',
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return [
            'id' => 'Идентификатор',
            'manufacturers_id' => 'Разработчик',
            'descr' => 'Наименование',
            'comment' => 'Комментарий',
            'items' => 'Основные элементы входящие в пакет ПО',
            'additional' => 'Дополнительные элементы входящие в пакет ПО',
	        'softLists_ids' => 'В списках ПО',
	        'created_at' => 'Дата добавления',
			'hitsCount'=>[
				'Уст.',
				'indexHint'=>'Количество обнаруженных установок продукта',
			],
			'compsCount'=>[
				'Пасп.',
				'indexHint'=>'Количество внесений продукта в паспорта АРМ'
			],
        ];
    }

	public function reverseLinks()
	{
		return [
			'ПО закреплено в паспортах ОС'=>$this->comps,
			$this->licGroups,
		];
	}
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLicGroups()
    {
		return $this->hasMany(LicGroups::className(), ['id' => 'lics_id'])
			->viaTable('{{%soft_in_lics}}', ['soft_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManufacturer()
    {
        return $this->hasOne(Manufacturers::className(), ['id' => 'manufacturers_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
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
        return static::getDb()->cache(function($db) {return $this->hasMany(SoftLists::className(), ['id' => 'list_id'])
            ->viaTable('{{%soft_in_lists}}', ['soft_id' => 'id']);},Manufacturers::$CACHE_TIME);
    }
	
	/**
	 * Возвращает набор компов, в которых закреплено ПО
	 */
	public function getComps()
	{
		return static::getDb()->cache(function($db) {return $this->hasMany(Comps::className(), ['id' => 'comp_id'])
			->viaTable('{{%soft_in_comps}}', ['soft_id' => 'id']);},Manufacturers::$CACHE_TIME);
	}
	
	public function getCompsCount() {return count($this->comps);}
	/**
	 * Возвращает набор компов, в которых находится ПО
	 */
	public function getHits()
	{
		if (SoftHits::allItemsLoaded()) {
			return ArrayHelper::getItemsByFields(SoftHits::getAllItems(),['soft_id'=>$this->id]);
		}
		
		return $this->hasMany(Comps::className(), ['id' => 'comp_id'])
			->viaTable('{{%soft_hits}}', ['soft_id' => 'id']);
	}
	public function getHitsCount() {return count($this->hits);}
	
	public function getName()
	{
		return	is_object($this->manufacturer)?$this->manufacturer->name.' '.$this->descr:$this->descr;
	}
	
	/**
     * Возвращает количество совпадения с переданными массивами входных строк
     * @param array $strings входные строки среди которых искать продукт
     * @param array $additional дополнительные строки среди которых искать дополнительные продукты, если есть совпадения в основных
     * @return array SoftHits
     */
    public function findHits($strings,$additional=[]) {
        $hits=new \app\models\SoftHits([
        	'masks'=>$this->getItemsArray(),
	        'additional_masks'=>$this->getAdditionalArray(),
	        'strings'=>$strings,
	        'additional_strings'=>$additional,
        ]);
        return $hits;
    }

	/**
     * Возвращает набор строк-допольниельных элементов ПО
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
	 * Возвращает набор строк-допольниельных элементов ПО
	 */
	public static function fetchAdditionalArray($id)
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
        return (array_search(SoftLists::getIgnoredListId(),$this->softLists_ids,false)!==false);
    }

    /**
     * Возвращает флаг наличия ПО в списке согласованного ПО
     */
    public function getIsAgreed()
    {
	    return (array_search(SoftLists::getAgreedListId(),SoftLists::getSoftLists($this->id),false)!==false);
	    return (array_search(SoftLists::getAgreedListId(),$this->softLists_ids,false)!==false);
    }

	/**
	 * Возвращает флаг наличия ПО в списке согласованного ПО
	 */
	public function getIsFree()
	{
		return (array_search(SoftLists::getFreeListId(),SoftLists::getSoftLists($this->id),false)!==false);
		return (array_search(SoftLists::getFreeListId(),$this->softLists_ids,false)!==false);
	}


	/**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array $items список элементов для вывода
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
     * @param array $items список элементов для вывода
     * @param string $keyField поле - ключ
     * @param string $valueField поле - значение
     * @param bool $asArray
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
		//error_log('savin');
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
		
		foreach ($comps as $comp) $comp->silentSave();
	}
	
	public static function fetchAll(){
		if (static::$disable_cache) return static::find()->all();
		if (is_null(static::$all_items)) {
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
