<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tech_types".
 *
 * @property int $id id
 * @property string $code Код
 * @property string $prefix Префикс
 * @property string $name Название
 * @property string $comment Комментарий
 *
 * @property TechModels[] $techModels
 */
class TechTypes extends \yii\db\ActiveRecord
{


	public static $title='Категории оборудования';
	public static $descr='Используемые категории различной техники для удобной группировки';
	//категории телефония
	public static $Phones=['voip_phone'];
	public static $Phones_ids=null;
	//какие категории относятся к ПК
	public static $PCs=['laptop','aio_pc','pc','srv'];
	public static $PCs_ids=null;
	//категории UPS
	public static $Ups=['ups'];
	public static $ups_ids=null;



	//кэш трансляции кодов в ИД и обратно
	private static $code_ids=[];
	private static $id_codes=[];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['code', 'name', 'prefix', 'comment'], 'required'],
            [['comment'], 'string'],
            [['code', 'name'], 'string', 'max' => 128,'min'=>2],
	        [['prefix'], 'string', 'max' => 16,'min'=>2],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'code' => 'Код',
			'name' => 'Название',
			'prefix' => 'Префикс инв. номера',
			'techModelsCount' => '# Моделей',
			'usages' => '# Обор-я',
			'comment' => 'Шаблон описаний',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			//'id' => 'id',
			'code' => 'Может использоваться впоследствии для отдельных обработчиков событий и генерации CSS классов в формах просмотра и отчетах',
			'name' => 'Понятное название типа техники: чб МФУ А4, VoIP Телефоны, ИБП, Радиостанции, WiFi AP, Маршрутизатор',
			'prefix' => 'При формировании инвентарного номера будет использоваться дополнительный префикс типа техники',
			'comment' => 'Те характеристики, которые нужно укаывзать при описании модели',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechModels()
	{
		return $this->hasMany(TechModels::className(), ['type_id' => 'id'])->orderBy('name');
	}

	/**
	 * @return int
	 */
	public function getTechModelsCount()
	{
		return count($this->techModels);
	}


	/**
	 * @return int
	 */
	public function getUsages()
	{
		$sum=0;
		foreach ($this->techModels as $model) $sum+=$model->usages;
		return $sum;
	}

	public static function fetchNames()
	{
		$list = static::find()
			->select(['id', 'name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

	/**
	 * Возвращает ИД категории по коду и кэширует запросы
	 * @param $code
	 * @return int|mixed
	 */
	public static function getCodeId($code) {
		if (isset(static::$code_ids[$code])) return static::$code_ids[$code];
		$obj=static::findOne(['code'=>$code]);
		return static::$code_ids[$code]=is_object($obj)?$obj->id:null;
	}

	/**
	 * наоборот
	 * @param integer $id
	 * @return string
	 */
	public static function getIdCode($id) {
		if (isset(static::$id_codes[$id])) return static::$id_codes[$id];
		return static::$id_codes[$id]=static::findOne($id)->code;
	}

	public static function fetchPhonesIds() {
		if (isset(static::$Phones_ids)) return static::$Phones_ids;
		static::$Phones_ids=[];
		foreach (static::$Phones as $code)
			static::$Phones_ids[]=static::getCodeId($code);
		return static::$Phones_ids;
	}

	public static function fetchPCsIds() {
		if (isset(static::$PCs_ids)) return static::$PCs_ids;
		static::$PCs_ids=[];
		foreach (static::$PCs as $code)
			static::$PCs_ids[]=static::getCodeId($code);
		return static::$PCs_ids;
	}

	public static function fetchUpsIds() {
		if (isset(static::$ups_ids)) return static::$ups_ids;
		static::$ups_ids=[];
		foreach (static::$Ups as $code)
			static::$ups_ids[]=static::getCodeId($code);
		return static::$ups_ids;
	}

	/**
	 * Возвращает признак того, что это оборудование ПК
	 * @param $id
	 * @return bool
	 */
	public static function isPC($id){
		return array_search($id,static::fetchPCsIds())!==false;
	}

	/**
	 * Возвращает признак того, что это оборудование Телефон
	 * @param $id
	 * @return bool
	 */
	public static function getIsPhone($id) {
		return array_search($id,static::fetchPhonesIds())!==false;
	}

	/**
	 * Возвращает признак того, что это оборудование Телефон
	 * @param $id
	 * @return bool
	 */
	public static function getIsUps($id) {
		return array_search($id,static::fetchUpsIds())!==false;
	}


}
