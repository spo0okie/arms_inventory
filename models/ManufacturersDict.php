<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "manufacturers_dict".
 *
 * @property int $id
 * @property string $word Вариант написания
 * @property int $manufacturers_id Производитель
 *
 * @property Manufacturers $manufacturers
 */
class ManufacturersDict extends ArmsModel
{
	static private $cache=[];
	static private $cacheComplete=false;
	
	/** @inheritdoc */
	static protected $syncableFields=[
		'word',
		'updated_at',
		'updated_by'
	];
	
	/** @inheritdoc */
	static public $syncKey='word';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'manufacturers_dict';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['word', 'manufacturers_id'], 'required'],
            [['manufacturers_id'], 'integer'],
            [['word'], 'string', 'max' => 255],
            [['word'], 'unique'],
            [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::className(), 'targetAttribute' => ['manufacturers_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'word' => 'Вариант написания',
            'manufacturers_id' => 'Производитель',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManufacturers()
    {
        return $this->hasOne(Manufacturers::className(), ['id' => 'manufacturers_id']);
    }

    public function beforeSave($insert) {
        //нам все слова нужны в нижнем регистре чтобы искать регистронезависимо
        $this->word = mb_strtolower($this->word);
        return parent::beforeSave($insert);
    }

    /**
     * Возвращает ID производителя по написанию или NULL если не найдет
     * @param string $word написание производителя
     * @return integer ID
     * @throws \Exception
     * @throws \Throwable
     */
    public static function fetchManufacturer($word){
    	$word=mb_strtolower($word,'utf-8');

    	if (!array_key_exists($word,static::$cache)) {
    		if (!static::$cacheComplete) {
    			//если кэш не загружался полностью ищем слово
				$item=static::find()->where(['word' => $word])->one();
				/**
				 * @var $item ManufacturersDict
				 */
				static::$cache[$word]=is_object($item)?$item->manufacturers_id:null;
			} else static::$cache[$word]=null;
	    }

        return static::$cache[$word];

    }
    
    public static function initCache() {
    	foreach (static::find()->all() as $item) {
			/**
			 * @var $item ManufacturersDict
			 */
			static::$cache[mb_strtolower($item->word,'utf-8')] = $item->manufacturers_id;
		}
    	static::$cacheComplete=true;
	}

}
