<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tech_models".
 *
 * @property int $id id
 * @property int $type_id Тип оборудования
 * @property bool $individual_specs Индивидуальные спеки
 * @property int $manufacturers_id Производитель
 * @property int $usages Количество экземпляров этой модели
 * @property string $name Модель
 * @property string $short Короткое наименование
 * @property string $shortest Самое короткое какое есть (или короткое или полное)
 * @property string $sname Расширенное имя для поиска
 * @property string $links Ссылки
 * @property string $comment Комментарий
 *
 * @property TechTypes $type
 * @property Techs[] $techs
 * @property Arms[] $arms
 * @property Manufacturers $manufacturer
 */
class TechModels extends \yii\db\ActiveRecord
{
	public static $title='Модели оборудования';
	public static $descr='Ну модели и модели. Что про них особо сказать';
	//подсказка которая передается через JSON если запрошена подсказка оформления спеки для моделей без спек
	public static $no_specs_hint='NO_SPECS_USE';

	private static $all_items=null;
	private static $names_cache=null;
	private static $phones_ids_cache=null;
	private static $pcs_ids_cache=null;
	private static $ups_ids_cache=null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_models';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['type_id', 'manufacturers_id', 'name', 'comment'], 'required'],
	        [['type_id', 'manufacturers_id', 'individual_specs'], 'integer'],
	        [['links', 'comment'], 'string'],
	        [['name'], 'string', 'max' => 128],
	        [['short'], 'string', 'max' => 24],
	        [['name'], 'unique'],
	        [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::className(), 'targetAttribute' => ['manufacturers_id' => 'id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechTypes::className(), 'targetAttribute' => ['type_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'type' => 'Тип оборудования',
			'type_id' => 'Тип оборудования',
			'manufacturers_id' => 'Производитель',
			'name' => 'Наименование',
			'short' => 'Короткое имя',
			'links' => 'Ссылки',
			'comment' => 'Описание',
			'usages' => 'Экз.',
			'individual_specs' => 'Индив. спеки',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'id',
			'type_id' => 'К какому типу оборудования относится эта модель',
			'manufacturers_id' => 'Производитель этой модели оборудования',
			'name' => 'Наименование модели (включая комплектацию, если бывают разные) достаточное для точной идентификации при закупке (имя производителя писать не надо)',
			'short' => 'Короткое название для вывода в плотных списках',
			'links' => \app\components\UrlListWidget::$hint,
			'comment' => 'Описание оборудования наиболее значимые параметры отличающие эту модель от других моделей того же типа оборудования',
			'individual_specs' => 'Признак того что модель не полностью определяет спецификацию оборудования, и для каждого экземпляра ее нужно описывать индивидуально (сервера, СХД, самосборные ПК)',
		];
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
	public function getType()
	{
		return $this->hasOne(TechTypes::className(), ['id' => 'type_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::className(), ['model_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArms()
	{
		return $this->hasMany(Arms::className(), ['model_id' => 'id']);
	}

	public function getUsages()
	{
		return count($this->techs)+count($this->arms);
	}

	public function getSname()
	{
		return
			//\app\models\TechTypes::fetchNames()[$this->type_id].' '.
			//\app\models\Manufacturers::fetchNames()[$this->manufacturers_id].' '.
			$this->type->name.' '.
			$this->manufacturer->name.' '.
			$this->name;
	}

	public function getShortest()
	{
		return strlen($this->short)?$this->short:$this->name;
	}

	public static function fetchAll(){
		if (!is_null(static::$all_items)) return static::$all_items;
		static::$all_items=[];
		foreach (static::find()->all() as $item) static::$all_items[$item['id']]=$item;
		return static::$all_items;
	}


	public static function fetchItem($id){
		return isset(static::fetchAll()[$id])?
			static::fetchAll()[$id]
			:
			null;
	}


	public static function fetchNames()
	{
		if (!is_null(static::$names_cache)) return static::$names_cache;
		$list = static::find()->joinWith('type')->joinWith('techs')->joinWith('manufacturer')
			//->where(['type_id'=>\app\models\TechTypes::fetchPCsIds()])
			//->select(['id', 'name'])
				//->orderBy('sname')
			->all();
		return static::$names_cache=\yii\helpers\ArrayHelper::map($list, 'id', 'sname');;
	}

	public static function fetchPCs()
	{
		$list = static::find()->joinWith('type')->joinWith('techs')->joinWith('manufacturer')
			->where(['type_id'=>\app\models\TechTypes::fetchPCsIds()])
			//->select(['id', 'name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

	public static function fetchPhones()
	{
		$list = static::find()->joinWith('type')->joinWith('techs')->joinWith('manufacturer')
			->where(['type_id'=>\app\models\TechTypes::fetchPhonesIds()])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

	public static function fetchPhonesIds()
	{
		if (is_null(static::$phones_ids_cache)) {
			$list = static::find()
				//->select('id')
				->joinWith('type')
				->where(['type_id'=>\app\models\TechTypes::fetchPhonesIds()])
				->all();
			static::$phones_ids_cache=\yii\helpers\ArrayHelper::getColumn($list,'id');
		}

		return static::$phones_ids_cache;
	}

	public static function fetchPCsIds()
	{
		if (is_null(static::$pcs_ids_cache)) {
			$list = static::find()
				//->select('id')
				->joinWith('type')
				->where(['type_id'=>\app\models\TechTypes::fetchPCsIds()])
				->all();
			static::$pcs_ids_cache=\yii\helpers\ArrayHelper::getColumn($list,'id');
		}

		return static::$pcs_ids_cache;
	}

	public static function fetchUpsIds()
	{
		if (is_null(static::$ups_ids_cache)) {
			$list = static::find()
				//->select('id')
				->joinWith('type')
				->where(['type_id'=>\app\models\TechTypes::fetchUpsIds()])
				->all();
			static::$ups_ids_cache=\yii\helpers\ArrayHelper::getColumn($list,'id');
		}

		return static::$ups_ids_cache;
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


	/**
	 * Возвращает описание поля комментарий для типа оборудования по модели
	 */
	public static function fetchTypeComment($id) {
		$model=static::findOne($id);
		$type=is_object($model)?$model->type:null;
		$comment_name=is_object($type)?$type->comment_name:null;
		$comment_hint=is_object($type)?$type->comment_hint:null;
		$typeModel=new \app\models\Techs();
		return [
			'name'=>strlen($comment_name)?
				$comment_name:
				$typeModel->attributeLabels()['comment'],
			'hint'=>strlen($comment_hint)?
				$comment_hint:
				$typeModel->attributeHints()['comment'],
		];
	}
}
