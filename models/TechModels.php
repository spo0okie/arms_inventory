<?php

namespace app\models;

use app\components\UrlListWidget;
use app\console\commands\SyncController;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tech_models".
 *
 * @property int $id id
 * @property int $type_id Тип оборудования
 *
 * @property bool $individual_specs Индивидуальные спеки
 * @property bool $contain_front_rack
 * @property bool $front_rack_two_sided
 * @property bool $contain_back_rack
 * @property bool $back_rack_two_sided
 * @property string $front_rack_layout
 * @property string $back_rack_layout
 * @property int $manufacturers_id Производитель
 * @property int $usages Количество экземпляров этой модели
 * @property string $name Модель
 * @property string        $short Короткое наименование
 * @property string        $shortest Самое короткое какое есть (или короткое или полное)
 * @property string        $nameWithVendor имя с вендором для синхронизации
 * @property string        $shortWithVendor самое короткое с вендором
 * @property string        $sname Расширенное имя для поиска
 * @property string        $links Ссылки
 * @property string        $comment Комментарий
 * @property string        $ports Порты
 * @property array         $portsList Порты
 *
 * @property TechTypes     $type
 * @property Techs[]       $techs
 * @property Manufacturers $manufacturer
 * @property int           $scans_id Картинка - предпросмотр
 * @property Scans[]       $scans
 * @property Scans         $preview
 */
class TechModels extends ArmsModel
{
	public static $title='Модель оборудования';
	public static $titles='Модели оборудования';
	public static $descr='Ну модели и модели. Что про них особо сказать';
	//подсказка которая передается через JSON если запрошена подсказка оформления спеки для моделей без спек
	public static $no_specs_hint='NO_SPECS_USE';

	private static $all_items=null;
	private static $names_cache=null;
	
	public $linksSchema=[
		'type_id'=>TechTypes::class,
		'manufacturers_id'=>Manufacturers::class,
		'scans_id'=>Scans::class,
	];
	
	/** @inheritdoc   */
	protected static $syncableFields=[
		'name',
		'short',
		'links',
		'comment',
		'individual_specs',
		'ports',
		'front_rack_layout',
		'contain_front_rack',
		'back_rack_layout',
		'contain_back_rack',
		'back_rack_two_sided',
		'updated_at',
		'updated_by',
	];
	
	public static $syncableDirectLinks=[
		'manufacturers_id'=>'Manufacturers',
		'type_id'=>'TechTypes',
		'scans_id'=>'Scans',
	];
	
	public static $syncableReverseLinks=[
		'Scans'=>'tech_models_id',
	];
	
	public static $syncKey='nameWithVendor';
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_models';
    }
    
    public function extraFields()
	{
		return['nameWithVendor'];
	}
	
	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['type_id', 'manufacturers_id', 'name', 'comment'], 'required'],
			[['type_id', 'manufacturers_id', 'individual_specs', 'scans_id'], 'integer'],
			[['contain_front_rack', 'contain_back_rack', 'front_rack_two_sided', 'back_rack_two_sided'], 'boolean'],
	        [['links', 'comment','ports'], 'string'],
	        [['name'], 'string', 'max' => 128],
	        [['short'], 'string', 'max' => 24],
	        [['name'], 'unique'],
			[['front_rack_layout','back_rack_layout'],'safe'],
			['contain_back_rack',function ($attribute) {
				if ($this->contain_front_rack && $this->front_rack_two_sided && $this->contain_back_rack)
					$this->addError($attribute,"Невозможно иметь одновременно и двустороннюю переднюю корзину и корзину сзади");
			}],
			['contain_front_rack',function ($attribute) {
				if ($this->contain_back_rack && $this->back_rack_two_sided && $this->contain_front_rack)
					$this->addError($attribute,"Невозможно иметь одновременно и двустороннюю заднюю корзину и корзину спереди");
			}],
	        [['manufacturers_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturers::className(), 'targetAttribute' => ['manufacturers_id' => 'id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechTypes::className(), 'targetAttribute' => ['type_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'id' => 'id',
			'back_rack_layout'=>[
				'Конфигурация задней корзины',
				'hint'=>'Может быть типовая (одна сетка на стенке устройства), в таком случае можно настраивать через конструктор<br>'.
					'Может быть более сложная (несколько секций столбцов и строк), в таком случае можно настраивать только через JSON конфигурацию',
			],
			'back_rack_two_sided'=>[
				'Двухсторонняя',
				'hint'=>'Посадочные места идут на всю глубину устройства и доступны для установки устройств как сзади так и спереди',
			],
			'contain_back_rack' => [
				'Посадочные места сзади',
				'hint'=>'Имеет посадочные места для дополнительных устройств (корзину) сзади'
			],
			'contain_front_rack' => [
				'Посадочные места спереди',
				'hint'=>'Имеет посадочные места для дополнительных устройств (корзину) спереди'
			],
			'comment' => [
				'Описание',
				'hint' => 'Описание оборудования наиболее значимые параметры отличающие эту модель от других моделей того же типа оборудования',
			],
			'front_rack_layout'=>[
				'Конфигурация передней корзины',
				'hint'=>'Может быть типовая (одна сетка на стенке устройства), в таком случае можно настраивать через конструктор<br>'.
					'Может быть более сложная (несколько секций столбцов и строк), в таком случае можно настраивать только через JSON конфигурацию',
			],
			'front_rack_two_sided'=>[
				'Двухсторонняя',
				'hint'=>'Посадочные места идут на всю глубину устройства и доступны для установки устройств как спереди так и сзади',
			],
			'individual_specs' => [
				'Индив. спеки',
				'hint' => 'Признак того что модель не полностью определяет спецификацию оборудования, и для каждого экземпляра ее нужно описывать индивидуально (сервера, СХД, самосборные ПК)',
			],
			'links' => [
				'Ссылки',
				'hint' => UrlListWidget::$hint,
			],
			'manufacturers_id' => [
				'Производитель',
				'hint' => 'Производитель этой модели оборудования',
			],
			'name' => [
				'Наименование',
				'hint' => 'Наименование модели (включая комплектацию, если бывают разные) достаточное для точной идентификации при закупке (имя производителя писать не надо)',
			],
			'ports' => [
				'Сетевые порты на устройстве',
				'hint' => 'Список Ethernet портов на устройстве, по строке на порт. Первое слово в строке - наименование порта (1/WAN/Lan_1/management), остальные слова - комментарий к порту',
			],
			'short' => [
				'Короткое имя',
				'hint' => 'Короткое название для вывода в плотных списках',
			],
			'type_id' => [
				'Тип оборудования',
				'hint' => 'К какому типу оборудования относится эта модель',
				'placeholder' => 'Выберите тип оборудования',
			],
			'type'=>['alias'=>'type_id'],
			'usages' => [
				'Экз.',
			],
		];
	}
	

	public function reverseLinks() {
		return [$this->techs];
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getManufacturer()
	{
		return $this->hasOne(Manufacturers::className(), ['id' => 'manufacturers_id']);
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		$scans=Scans::find()->where(['tech_models_id' => $this->id ])->all();
		$scans_sorted=[];
		foreach ($scans as $scan) if($scan->id == $this->scans_id) $scans_sorted[]=$scan;
		foreach ($scans as $scan) if($scan->id != $this->scans_id) $scans_sorted[]=$scan;
		return $scans_sorted;
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getPreview()
	{
		if (!$this->scans_id) return null;
		return Scans::find()->where(['id' => $this->scans_id ])->one();
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getType()
	{
		return $this->hasOne(TechTypes::className(), ['id' => 'type_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::className(), ['model_id' => 'id']);
	}

	public function getUsages()
	{
		return count($this->techs);
	}
	
	public function getPortsList()
	{
		if(!count($ports=explode("\n",$this->ports))) return [];
		$model_ports=[];
		foreach ($ports as $port) {
			$tokens=explode(' ',$port);
			
			//вытаскиваем первое слово
			$port_name=trim($tokens[0]);
			unset ($tokens[0]);
			
			//остальные слова - комментарий
			if (strlen($port_name)) $model_ports[(string)$port_name]=trim(implode(' ',$tokens));
		}
		return $model_ports;
	}
	
	public function getPortComment($port) {
		$ports=$this->portsList;
		if (isset($ports[$port])){
			return $ports[$port];
		}
		return null;
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
	
	public function getNameWithVendor()
	{
		return	(is_object($this->manufacturer)?$this->manufacturer->name.' ':'')
			.$this->name;
	}
	
	public function getShortWithVendor()
	{
		return	(is_object($this->manufacturer)?$this->manufacturer->name.' ':'')
			.$this->shortest;
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
		return static::$names_cache= ArrayHelper::map($list, 'id', 'sname');
	}

	public static function fetchPCs()
	{
		$list = static::find()->joinWith('type')->joinWith('techs')->joinWith('manufacturer')
			->where(['is_computer'=>true])
			//->select(['id', 'name'])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}

	public static function fetchPhones()
	{
		$list = static::find()->joinWith('type')->joinWith('techs')->joinWith('manufacturer')
			->where(['is_phone'=>true])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}

	/*public static function fetchPhonesIds()
	{
		if (is_null(static::$phones_ids_cache)) {
			$list = static::find()
				//->select('id')
				->joinWith('type')
				->where(['is_phone'=>true])
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
	
	public static function fetchMonitorsIds()
	{
		if (is_null(static::$monitors_ids_cache)) {
			$list = static::find()
				//->select('id')
				->joinWith('type')
				->where(['type_id'=>\app\models\TechTypes::fetchMonitorIds()])
				->all();
			static::$monitors_ids_cache=\yii\helpers\ArrayHelper::getColumn($list,'id');
		}
		
		return static::$monitors_ids_cache;
	}*/
	
	/**
	 * Возвращает признак того, что это оборудование ПК
	 * @return bool
	 */
	public function getIsPC(){
		if (isset($this->attrsCache['isPC'])) return $this->attrsCache['isPC'];
		return $this->attrsCache['isPC']=$this->type->is_computer;
	}

	/**
	 * Возвращает признак того, что это оборудование Телефон
	 * @return bool
	 */
	public  function getIsPhone() {
		if (isset($this->attrsCache['isPhone'])) return $this->attrsCache['isPhone'];
		return $this->attrsCache['isPhone']=$this->type->is_phone;
	}
	
	/**
	 * Возвращает признак того, что это оборудование Телефон
	 * @return bool
	 */
	public  function getIsUps() {
		if (isset($this->attrsCache['isUps'])) return $this->attrsCache['isUps'];
		return $this->attrsCache['isUps']=$this->type->is_ups;
	}
	
	/**
	 * Возвращает признак того, что это оборудование Монитор
	 * @return bool
	 */
	public function getIsMonitor() {
		if (isset($this->attrsCache['isMonitor'])) return $this->attrsCache['isMonitor'];
		return $this->attrsCache['isMonitor']=$this->type->is_display;
	}
	
	
	/**
	 * Возвращает описание поля комментарий для типа оборудования по модели
	 */
	public static function fetchTypeComment($id) {
		$model=static::findOne($id);
		$type=is_object($model)?$model->type:null;
		$comment_name=is_object($type)?$type->comment_name:null;
		$comment_hint=is_object($type)?$type->comment_hint:null;
		$typeModel=new Techs();
		return [
			'name'=>strlen($comment_name)?
				$comment_name:
				$typeModel->attributeLabels()['comment'],
			'hint'=>strlen($comment_hint)?
				$comment_hint:
				$typeModel->attributeHints()['comment'],
		];
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (is_object($this->manufacturer)) {
				//если есть производитель, то его название надо бы убрать из имени софта
				$this->name=$this->manufacturer->cropVendorName($this->name);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function syncFindLocal($name) {
		/*echo static::find()
				->joinWith('manufacturer')
				->where(['LOWER(CONCAT(manufacturers.name,\' \',tech_models.name))'=>mb_strtolower($name)])
				->createCommand()->rawSql."!!\n";*/
		$query=static::find()
			->joinWith('manufacturer')
			->where(['LOWER(CONCAT(manufacturers.name,\' \',tech_models.name))'=>mb_strtolower($name)]);
		
		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Searching local $class: ".$query->createCommand()->rawSql."\n";
		}
		return $query->all();
	}
	
	
}
