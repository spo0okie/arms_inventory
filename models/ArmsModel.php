<?php

namespace app\models;

use app\components\DynaGridWidget;
use app\console\commands\SyncController;
use app\helpers\ArrayHelper;
use app\helpers\RestHelper;
use DateTime;
use DateTimeZone;
use Yii;

/**
 * This is the model class for table "arms".
 *
 * @property int $id Идентификатор
 * @property string $name Имя экземпляра
 * @property string $sname Имя для поиска
 * @property string $comment Комментарий
 * @property string $history история
 * @property string $updatedAt Время обновления
 * @property string $updated_at Время обновления
 * @property string $updated_by Автор обновления
 * @property Attaches $attaches Загруженные файлы
 * @property boolean $archived Статус архивирования элемента
 * @property string $external_links Внешние ссылки
 
 * @property int $secondsSinceUpdate Секунды с момента обновления
 */
class ArmsModel extends \yii\db\ActiveRecord
{
	public static $title='Объект';
	public static $titles='Объекты';
	
	public const searchableOrHint='<br><i>HINT: Можно искать несколько вариантов, разделив их вертикальной</i> <b>|</b> <i>чертой</i>';
	
	
	protected $attributeDataCache=null;
	protected $attributeLabelsCache=null;
	
	private static $allItems=null;
	
	/** @var bool при сохранении не менять отметку времени и не менять время обновления */
	protected $doNotChangeAuthor=false;

	// (для функционала импорта/синхронизации)
	
	/** @var array поля которые у этой модели можно синхронизировать с удаленной системы */
	protected static $syncableFields=[];
	
	/** @var array ссылки других объектов на этот, которые надо синхронизировать */
	public static $syncableReverseLinks=[];
	
	/** @var array ссылки этого объекта на другие, которые надо синхронизировать */
	public static $syncableDirectLinks=[];
	
	/** @var array many-many ссылки этого объекта на другие, которые надо синхронизировать */
	public static $syncableMany2ManyLinks=[];

	/** @var string|null ключ, по которому можно найти такой объект на удаленной системе */
	public static $syncKey='name';
	
	/** @var string поле которое сравнивается  */
	public static $syncTimestamp='updated_at';
	
	/**
	 * Массив описания полей
	 */
	public function attributeData()
	{
		return [
			'id' => [
				'Идентификатор',
			],
			'comment' => [
				'Примечание',
				'hint' => 'Краткое пояснение по этому объекту',
			],
			'notepad' => [
				'Записная книжка',
				'hint' => 'Все важные и не очень заметки и примечания по жизненному циклу этого объекта',
			],
			'history' => ['alias'=>'notepad'],
			'updated_at' => [
				'Время изменения',
				'hint' => 'Время обновления объекта в БД'
			],
			'updated_by'=>[
				'Редактор',
				'hint' => 'Автор последних изменений объекта'
			],
			'external_links' => [
				'Внешние ссылки',
				'hint'=>'JSON структура с идентификаторами этого объекта во внешних ИС'
			],
		];
	}

	public function getAttributeData($key)
	{
		if (is_null($this->attributeDataCache)) {
			$this->attributeDataCache=$this->attributeData();
		}
		
		if (!isset($this->attributeDataCache[$key])) {
			return null;
		}
		
		$data=$this->attributeDataCache[$key];
		if (!isset($data['alias'])) return $data;
		if ($data['alias']==$key) return $data; //no recursion!
		
		return $this->getAttributeData($data['alias']);
	}
	
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
    	if (is_null($this->attributeLabelsCache)) {
			$this->attributeLabelsCache=[];
			foreach ($this->attributeData() as $key=>$data) {
				$data=$this->getAttributeData($key);
				if (is_array($data)) {
					$label=null;
					if (isset($data[0]))
						$this->attributeLabelsCache[$key]=$data[0];
					elseif (isset($data['label']))
						$this->attributeLabelsCache[$key]=$data['label'];
				} else $this->attributeLabelsCache[$key]=$data;
			}
		}
        return $this->attributeLabelsCache;
    }

	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		$hints=[];
		foreach ($this->attributeData() as $key=>$data) {
			$data=$this->getAttributeData($key);
			if (is_array($data) && isset($data['hint']))
				$hints[$key]=$data['hint'];
			
		}
		return $hints;
	}
	
	/**
	 * Возвращает наименование атрибута для формы поиска
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeIndexLabel($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (is_array($item) && isset($item['indexLabel']))
			return $item['indexLabel'];
		return $this->getAttributeLabel($attribute);
	}
	
	
	/**
	 * Возвращает описание атрибута для формы поиска
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeIndexHint($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (isset($item['indexHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['indexHint']
			);
		return null;
	}
	
	/**
	 * Возвращает ссылки на объекты ссылающиеся на этот
	 * по схеме one-to-many и many-to-many
	 * @return array
	 */
	public function reverseLinks() {
		return [];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAttaches() {
		return $this->hasMany(Attaches::class,[static::tableName().'_id'=>'id'	]);
	}
	
	public static function fetchNames(){
		$list= static::find()
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

	public function getSname(){
		return $this->name;
	}
	
	public function getSecondsSinceUpdate() {
		$updated = new	DateTime($this->updated_at,	new DateTimeZone('UTC') );
		return time()-$updated->format('U');
	}
	
	
	public static function allItemsLoaded() {
		return !is_null(static::$allItems);
	}
	
	public static function cacheAllItems() {
		if (!static::allItemsLoaded())
			static::$allItems=ArrayHelper::index(static::find()->all(),'id');
	}
	
	public static function getAllItems($autoload=false) {
		if (!static::allItemsLoaded() && $autoload)
			static::cacheAllItems();
		return static::$allItems;
	}
	
	public static function getLoadedItem($id,$autoload=false) {
		if (!static::allItemsLoaded()) {
			if ($autoload)
				static::cacheAllItems();
			else
				return null;
		}
		return isset(static::$allItems[$id])?static::$allItems[$id]:null;
	}
	
	/**
	 * Валидация отсутствия рекурсии при построении ссылок на родителей
	 * @param       $attribute - аттрибут с id другого объекта
	 * @param array $params
	 * в параметрах надо указать 'params'=>['getLink'=>'parentService'] - метод которым получить не id а объект
	 */
	public function validateRecursiveLink($attribute, $params=[])
	{
		$params=(array)$params;
		//если у нас нет цепочки связей - создаем пустую
		if (!isset($params['attributeChain']))	$params['attributeChain']=[];
		//кладем себя в цепочку
		$params['attributeChain'][]=$this->id;
		
		//если никакой другой объект не передан, то проверяем себя
		$object=isset($params['object'])?$params['object']:$this;
		
		//метод для получения связанного объекта
		$getLink=$params['getLink'];
		
		//если у нас есть ссылка
		if (!empty($object->$attribute)) {
			//если она уже есть в цепочке id
			if (in_array($object->$attribute, $params['attributeChain'])) {
				$this->addError($attribute, $this->getAttributeLabel($attribute).' рекурсивно ссылается сам на себя');
			} else {
				//иначе пробуем загрузить объект на который ссылаемся
				if(is_object($link=$object->$getLink)) {
					//кладем его в параметры для следующей проверки
					$params['object']=$link;
					//проверяем
					$this->validateRecursiveLink($attribute, $params);
				}
			}
		}
	}
	
	public function silentSave($runValidation = true) {
		$this->doNotChangeAuthor=true;
		return $this->save($runValidation);
	}
	
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;
		
		// Механизм обновления поля external_links такой что задавая какую-то внешнюю ссылку
		// - она просто добавляется к существующим
		if ($this->hasProperty('external_links') && $this->external_links) {
			$old=static::findOne($this->id);
			$current=$old->external_links?json_decode($old->external_links,true):[];
			$new=json_decode($this->external_links,true);
			$merged=ArrayHelper::recursiveOverride($current,$new);
			$this->external_links=json_encode($merged,JSON_UNESCAPED_UNICODE);
		}
		
		if ($this->hasProperty('updated_at') && !$this->doNotChangeAuthor) {
			$this->updated_at=gmdate('Y-m-d H:i:s');
		}
		
		if ($this->hasProperty('updated_by') && !$this->doNotChangeAuthor) {
			if (Yii::$app->hasProperty('user') && is_object(Yii::$app->user) && is_object(Yii::$app->user->identity))
				$this->updated_by=Yii::$app->user->identity->Login;
		}
		
		return true;
	}
	
	
	/**
	 * Загрузить поля с объекта загруженного с другой системы (такой же инвентори)
	 * @param array      $remote сам удаленный объект
	 * @param array      $overrides поля которым надо задать кастомные значения (для ссылок, их нельзя взять из удаленного)
	 * @param string     $log
	 * @param RestHelper $rest
	 * @return bool|null
	 */
	public function syncFields(array $remote, array $overrides, string &$log, RestHelper $rest) {
		$timestamp=static::$syncTimestamp;
		
		foreach ($overrides as $field=>$value) {
			if ($this->$field==$value) unset ($overrides[$field]);
		}
		
		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Comparing $class $timestamp: Local={$this->$timestamp} vs Remote={$remote[$timestamp]}\n";
		}
		
		
		//если (удаленный объект имеет отметку времени и она больше) или надо поменять (а не синхронизировать) какие-то поля
		if (($timestamp && $remote[$timestamp] && $remote[$timestamp]>$this->$timestamp) || count($overrides)) {
			$needUpdate=false;
			foreach (static::$syncableFields as $field) {
				if ($this->$field != $remote[$field]) {
					$log.= "$field: [{$this->$field} != {$remote[$field]}]; ";
					$this->$field = $remote[$field];
					$needUpdate=true;
				}
			}
			foreach ($overrides as $field=>$value) {
				if (strpos($field,'_ids')==(strlen($field)-4)) {
					if (array_search($value,$this->$field)===false) {
						echo "$field: [+ $value]; ";
						$this->$field = array_merge($this->$field,[$value]);
						$needUpdate=true;
					}
				} else {
					if ($this->$field != $value) {
						$this->$field = $value;
						$log .= "$field: [{$this->$field} => $value]; ";
						$needUpdate=true;
					}
				}
			}
			if (!$needUpdate) return null;
			return $this->silentSave(false);
		}
		//если менять не надо
		return null;
	}
	
	/**
	 * Создает объект в локальной системе на основании данных из удаленной
	 * @param array      $remote сам удаленный объект
	 * @param array      $overrides поля которым надо задать кастомные значения (для ссылок, их нельзя взять из удаленного)
	 * @param string     $log
	 * @param RestHelper $rest
	 * @return ArmsModel
	 */
	public static function syncCreate(array $remote, array $overrides, string &$log, RestHelper $rest) {
		
		$import=[];
		
		foreach (static::$syncableFields as $field) {
			$import[$field]=$remote[$field];
		}
		
		foreach ($overrides as $field=>$value) {
			if (strpos($field,'_ids')==(strlen($field)-4)) {
				$import[$field] = [$value];
			} else {
				$import[$field]=$value;
			}
		}
		
		foreach ($import as $p=>$v) {
			if (is_array($v)) $v=implode(',',$v);
			$log.= "[$p=>$v]; ";
		}

		return new static($import);
	}
	
	/**
	 * Как найти локальные объекты по ключу синхронизации
	 * (который на самом деле никакой не ключ с точки зрения БД)
	 * @param $name
	 * @return ArmsModel[]
	 */
	public static function syncFindLocal($name) {
		$query=static::find()->where([static::$syncKey=>$name]);
		if (SyncController::$debug) {
			$class=SyncController::getClassName(static::class);
			echo "Searching local $class: ".$query->createCommand()->rawSql."\n";
		}
		return $query->all();
	}
	
}
