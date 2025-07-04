<?php

namespace app\models;

use app\console\commands\SyncController;
use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\helpers\RestHelper;
use app\helpers\StringHelper;
use app\models\traits\AttributeDataModelTrait;
use app\models\traits\AttributeLinksModelTrait;
use app\models\traits\ExternalDataModelTrait;
use DateTime;
use DateTimeZone;
use Throwable;
use Yii;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\web\View;

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
 * @property string $links Ссылки прикрепленные к объекту
 * @property Attaches $attaches Загруженные файлы
 * @property boolean $archived Статус архивирования элемента
 * @property boolean $canBeArchived может иметь статус архивировано
 * @property boolean $isArchived может иметь статус архивировано и он выставлен
 * @property string $external_links Внешние ссылки
 * @property string $historyClass Класс, который хранит журнал изменений моделей класса
 * @property string $controllerPath Путь до контроллера (для URI)
 * @property string $viewsPath Путь до папки Views (для рендера)
 
 * @property int $secondsSinceUpdate Секунды с момента обновления
 */
class ArmsModel extends ActiveRecord
{
	use ExternalDataModelTrait,AttributeDataModelTrait,AttributeLinksModelTrait;
	
	/** @var string как называется один экземпляр модели (для страницы Create -> Новый объект) */
	public static $title='Объект';
	
	/** @var string как называется список моделей (для страницы Index) */
	public static $titles='Объекты';
	
	/** @var string надпись на кнопке создания нового объекта в списке */
	public static $addButtonText='Добавить';
	
	/** @var null|string подсказка для кнопки создания нового объекта */
	public static $addButtonHint=null;
	
	/** @var string Префикс для страницы Create (Новый $title) */
	public static $newItemPrefix='Новый';
	
	/**
	 * @var string Атрибут, который считается именем модели.
	 * Его будет выводить ->renderItem,
	 * по нему будет искать search-by-name
	 */
	public static $nameAttr='name';
	
	public const searchableOrHint='<br><i>HINT: Можно искать несколько вариантов, разделив их вертикальной</i> <b>|</b> <i>чертой</i>';
	
	
	protected $historyClass;	//если заполнить, то будет сохранять историю в моделях этого класса
	
	/** @var array Кэш для рекурсивного поиска поля (Когда значение может быть в родителе и в его родителе или ...) */
	protected $recursiveCache=[];
	
	/** @var array Кэш для вычисляемых аттрибутов */
	protected $attrsCache=[];
	
	protected static $allItems=null;
	
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

	
	const SCENARIO_VALIDATION = 'validation';
	
	
	/**
	 * Прикручиваем поведения для many-2-many ссылок
	 * @return array
	 */
	public function behaviors()
	{
		return [
			$this->relationsBehaviour(),
		];
	}
	
	public function extraFields()
	{
		$fields = array_unique(
			array_merge(
				array_keys(parent::extraFields()),
				array_keys($this->getLinksSchema()) //все ссылки many-2-many
			)
		);
		
		return array_combine($fields, $fields);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAttaches() {
		return $this->hasMany(Attaches::class,[static::tableName().'_id'=>'id'	]);
	}
	
	public static function fetchNames(){
		$list= static::find()
			//->select(['id','name'])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}
	
	public function getName(){
		return $this->{static::$nameAttr};
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
	 * в параметрах надо указать 'params'=>[
	 *     	'getLink'=>'parentService',    	//обязательно! - метод которым получить не id а объект
	 *     	'initialLink'=>ArmsModel[],    	//можно передать первую итерацию ссылок, т.к. LinkerBehaviour читает ссылки
	 * 										//из базы а не из переменной, и несохраненные в БД ссылки через getter не получить
	 *		'origin'						//кому будем писать ошибки валидации (инициатор валидации)
	 * 		'object'						//чьи аттрибуты проверяем
	 * 		'attributeChain'				//накопленные значения поля за время движения по рекурсии
	 * ]
	 * @return bool
	 */
	public function validateRecursiveLink($attribute, $params=[])
	{
		$params=(array)$params;
		
		// кладем инициатора рекурсии в параметры
		if (!isset($params['origin'])) $params['origin']=$this;
		//если у нас нет цепочки связей - создаем пустую
		if (!isset($params['attributeChain']))	$params['attributeChain']=[];
		//кладем себя в цепочку
		$params['attributeChain'][]=$this->id;
		
		//если никакой другой объект не передан, то проверяем себя
		$object=isset($params['object'])?$params['object']:$this;
		
		//метод для получения связанного объекта
		$getLink=$params['getLink']??'parent';
		
		//если у нас есть ссылка
		if (!empty($object->$attribute)) {
			//предположим что у нас тут может быть и _id и _ids, т.к. _ids более общий - используем его
			if (!is_array($link_ids=$object->$attribute)) $link_ids=[$link_ids];
			//если она уже есть в цепочке id
			foreach ($link_ids as $link_id) {
				if (in_array($link_id, $params['attributeChain'])) {
					$error=($this->hasProperty('name')?$this->name:$this->getAttributeLabel($attribute))
						.' рекурсивно ссылается сам на себя';
					$params['origin']->addError($attribute, $error);
					return false; //нет смысла дальше проверять
				}
			}
			//иначе пробуем загрузить объекты на которые ссылаемся
			if (isset($params['initialLink'])) {//если ссылки передали через параметры
				$links=$params['initialLink'];	//то из и используем (нужно для links_ids до их записи в БД)
				unset($params['initialLink']);	//дальше будем использовать ссылки уже из БД
			} else {
				$links=$object->$getLink;
			}
			//links также может быть и getService и getServices, приводим в общем случае к массиву
			if (!is_array($links)) $links=[$links];
			foreach ($links as $link) {
				if (is_object($link)) {
					//кладем его в параметры для следующей проверки
					$params['object']=$link;
					//проверяем
					if (!$link->validateRecursiveLink($attribute, $params)) return false; //если нашли косяк, дальше не проверяем
				}
			}
		}
		return true;
	}
	
	/**
	 * Проверяем что поле $attr у модели не заполнено
	 * @param $model
	 * @param $attr
	 * @return bool
	 */
	public static function attrIsEmpty($model,$attr) {
		if (StringHelper::endsWith($attr,'_ids')) {
			//такие аттрибуты это массивы, они должны содержать хоть один элемент
			if (!is_array($model->$attr)) return true; //не массив
			if (!count($model->$attr)) return true; //пустой
		}
		
		if (StringHelper::endsWith($attr,'_id')) {
			//такие аттрибуты это ссылки, они должны указывать на что-то отличное от нуля
			if (!is_numeric($model->$attr)) return true; //не число
			if (!($model->$attr>0)) return true; //ноль
		}
		
		return empty($model->$attr);
	}
	
	/**
	 * Требовать выставления как минимум одного аттрибута из нескольких
	 * $params должно содержать поле 'attrs' => ['comp_ids','comment','user_id'];
	 * обязательно при прописывании валидатора надо добавлять параметр 'skipOnEmpty' => false
	 * иначе валидация пустых полей будет пропущена
	 * @param       $attribute
	 * @param array $params
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function validateRequireOneOf($attribute, $params=[]) {
		foreach ($params['attrs']??[] as $attr) {
			//если аттрибут не пуст, то как минимум один заполнен
			if (!static::attrIsEmpty($this,$attr)) return true;
		}
		foreach ($params['attrs']??[] as $attr) {
			$this->addError($attr,$params['message']??'Как минимум один аттрибут должен быть заполнен');
		}
		return false;
	}
	
	/**
	 * Проверяет, что аттрибут или integer или их массив
	 * @param $attribute
	 */
	public function validateIntegerOrArrayOfInteger($attribute)
	{
		if (!is_int($this->$attribute) &&
			!(is_array($this->$attribute) &&
				count(array_filter($this->$attribute, 'is_int')) == count($this->$attribute))) {
			$this->addError($attribute, 'ID должен быть целым числом или массивом целых чисел');
		}
	}
	
	/**
	 * Сохранить без обновления журнала
	 * @param bool $runValidation
	 * @return bool
	 * @throws Exception
	 */
	public function silentSave($runValidation = true) {
		$this->doNotChangeAuthor=true;
		return $this->save($runValidation);
	}
	
	/**
	 * Возвращает класс журнала этой модели. Либо он должен быть явно задан,
	 * либо должен существовать класс с суффиксом History
	 * @return false|string
	 */
	public function getHistoryClass() {
		//упростим себе задачу тем что класс не надо задавать всегда вручную, пусть будет {$MasterClass}History
		if (!isset($this->historyClass)) {
			//у моделей которые сами журналы истории такое не нужно
			if ($this instanceof HistoryModel) return false;
			$this->historyClass=static::class.'History';
		}
		if (!class_exists($this->historyClass)) return false;
		return $this->historyClass;
	}
	
	/**
	 * Записывает в журнал изменения (если они обнаружатся относительно предыдущей записи в журнале)
	 * @param null $initiator
	 */
	public function historyCommit($initiator=null) {
		$historyClass=$this->getHistoryClass();
		if (!$historyClass || $this->doNotChangeAuthor) return;
		
		//ну что ж, давайте попробуем залепить запись в журнал!
		/** @var HistoryModel $journal */
		$journal=new $historyClass();
		$journal->journal($this,$initiator);
	}
	
	/**
	 * Записывает в журнал отметку об удалении объекта
	 * @param null $initiator
	 */
	public function historyEnd($initiator=null) {
		if (!($historyClass=$this->getHistoryClass())) return;
		
		/** @var HistoryModel $journal */
		$journal=new $historyClass();
		$journal->journalDeletion($this,$initiator);
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		
		$this->historyCommit(); //журналирование изменений
	}
	
	public function afterDelete()
	{
		parent::afterDelete();

		$this->historyEnd(); //журналирование удаления
	}
	
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;
		
		
		if ($this->hasProperty('updated_at') && !$this->doNotChangeAuthor) {
			$this->updated_at=gmdate('Y-m-d H:i:s');
		}
		
		if ($this->hasProperty('updated_by') && !$this->doNotChangeAuthor) {
			if (Yii::$app->hasProperty('user') && is_object(Yii::$app->user) && is_object(Yii::$app->user->identity))
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$this->updated_by=Yii::$app->user->identity->Login;
		}
		
		if ($this->hasProperty('external_links')) {
			$this->externalDataBeforeSave();
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
	 * @noinspection PhpUnusedParameterInspection
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
				} elseif ($this->$field != $value) {
					$this->$field = $value;
					$log .= "$field: [{$this->$field} => $value]; ";
					$needUpdate=true;
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
	 * @noinspection PhpUnusedParameterInspection
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
	
	public static function fetchNextValue($field) {
		$max=static::find()->select("MAX(CAST(`$field` as SIGNED))")->scalar();
		return ++$max;
	}
	
	public static function fetchNextId() {
		return static::fetchNextValue('id');
	}
	
	/**
	 * Поиск объекта по имени
	 * @param string $name
	 * @return ArmsModel|ActiveRecord|null
	 */
	public static function findByName(string $name)	{
		return static::find()
			->where(['LOWER(name)'=>strtolower($name)])
			->one();
	}
	
	/**
	 * @param string $name
	 * @return ArmsModel|ActiveRecord|null
	 */
	public static function findByAnyName(string $name)
	{
		return static::findByName($name);
	}
	
	/**
	 * Рекурсивный поиск аттрибута в цепочке родителей
	 * @param string 		$simpleAttr		как называется локальный аттрибут без учета рекурсии
	 * @param string|null	$recursiveAttr	как называется этот же аттрибут с учетом рекурсии
	 * 										(если не указать то рекурсивно будет вызвана эта же функция)
	 * @param string|null	$parent			как называется ссылка на батю
	 * @param null   		$empty			что вернуть если ничего не нашли
	 * @return mixed|null
	 */
	public function findRecursiveAttr(string $simpleAttr, string $recursiveAttr=null, $parent=null, $empty=null) {
		//ищем в кэше
		if (isset($this->recursiveCache[$simpleAttr]))
			return $this->recursiveCache[$simpleAttr];
		
		if (!is_null($recursiveAttr)&&isset($this->recursiveCache[$recursiveAttr]))
			return $this->recursiveCache[$recursiveAttr];
		
		//ищем у себя
		$value=$this->$simpleAttr;
		if (is_object($value)||(is_array($value)&&count($value))||!empty($value))
			return $this->recursiveCache[$recursiveAttr] = $value;
		
		//атрибут ссылка на предка
		if (is_null($parent)) $parent=$this->parentAttr;
		
		//ищем у родителя
		if (is_object($this->$parent)) {
			if (is_null($recursiveAttr)) {
				return $this->recursiveCache[$simpleAttr] = $this->$parent->findRecursiveAttr($simpleAttr,$recursiveAttr,$parent,$empty);
			} else {
				return $this->recursiveCache[$recursiveAttr] = $this->$parent->$recursiveAttr;
			}
		}
		
		//запоминаем, что ничего не нашли
		return $this->recursiveCache[$recursiveAttr] = $empty;
	}
	
	/**
	 * Возвращает узел в дереве предков в котором задан наследуемый атрибут
	 * @param string $attr
	 * @param string|null $parentAttr
	 * @return int|null
	 */
	public function findRecursiveAttrNode(string $attr, $parentAttr=null) {
		//проверяем для рекурсивных аттрибутов somethingRecursive -> something
		if (StringHelper::endsWith($attr,'Recursive')) {
			$attr=substr($attr,0,strlen($attr)-strlen('Recursive'));
		}
		
		if (isset($this->recursiveCache[$attr.'::node']))
			return $this->recursiveCache[$attr.'::node'];
		
		//атрибут ссылка на предка
		if (is_null($parentAttr)) $parentAttr=$this->parentAttr;
		
		$test=$this;
		while (is_object($test)) {
			$value=$test->$attr;
			if (is_object($value)||(is_array($value)&&count($value))||!empty($value)) {
				return $this->recursiveCache[$attr.'::node']=$test;
			}
			//переключаемся на родителя
			$test=$test->$parentAttr;
		}
		return $this->recursiveCache[$attr.'::node']=null;
	}
	
	/**
	 * Возвращает текстовое поле в котором может быть указано {{PARENT}} и оно будет заменено на значение
	 * этого поля в родителе модели (имеет смысл только в моделях с иерархией)
	 * @param string    $field	поле (например comment) в котором может быть {{PARENT}}
	 * @param string|null    $recursiveField рекурсивное поле (например commentRecursive) в котором {{PARENT}} будет уже заменено
	 * @return string|null
	 */
	public function textRecursiveField(string $field, $recursiveField=null)
	{
		$PARENT='{{PARENT}}';
		$text=$this->$field;
		if (strpos($text,$PARENT)===false) return $text;
		
		$parentAttr=$this->parentAttr;
		if (!$this->canGetProperty($parentAttr)) return $text;
		/** @var ArmsModel $parent */
		$parent=$this->$parentAttr;
		$parentText='';
		
		if (is_object($parent)) {
			$parentText=$recursiveField?
				$parent->$recursiveField:
				$parent->textRecursiveField($field);
		};
		
		return str_replace($PARENT, $parentText, $text);
	}
	
	public function externalData() {}
	
	/**
	 * Вытащить запись журнала на дату
	 * @param $id
	 * @param $timestamp
	 * @return ActiveRecord
	 */
	public static function fetchJournalRecord($id,$timestamp) {
		//достаем класс журнала
		$instance=new static();
		/** @var HistoryModel $historyClass */
		$historyClass=$instance->getHistoryClass();
		
		//если класс журнала есть, ищем запись в журнале
		if ($historyClass) {
			$record=$historyClass::findOnTimestamp($id,$timestamp);
			//нашли - молодцы!
			if (is_object($record)) return $record;
		}
		//ищем текущую запись в оперативной таблице (не в журнале)
		return static::findOne($id);
	}
	
	/**
	 * Построить путь от потомка к предку
	 * @param View   $view
	 * @param string $label	какой аттрибут использовать в качестве имени модели
	 */
	public function recursiveBreadcrumbs(View $view, $label='name') {
		$item=$this;
		$chain=[$this];
		$parent=$this->parentAttr;
		$viewPath='/'.StringHelper::class2Id(get_class($this)).'/view';
		while (is_object($item=$item->$parent)) {
			$chain[]=$item;
		}
		foreach (array_reverse($chain) as $item) {
			$view->params['breadcrumbs'][]=[
				'label'=>$item->$label,
				'url'=>[$viewPath,'id'=>$item->id],
			];
		}
	}
	
	/**
	 * Признак того, что эта модель может иметь статус "архивировано"
	 * @return bool
	 */
	public function getCanBeArchived() {
		if (isset($this->attrsCache['canBeArchived'])) return $this->attrsCache['canBeArchived'];
		return $this->attrsCache['canBeArchived']=$this->hasProperty('archived');
	}
	
	/**
	 * Признак того, что эта модель имеет статус "архивировано"
	 * @return bool
	 */
	public function getIsArchived() {
		return $this->canBeArchived && $this->archived;
	}
	
	/**
	 * Путь до контроллера
	 * @return mixed|string
	 */
	public function getControllerPath() {
		if (isset($this->attrsCache['controllerPath'])) return $this->attrsCache['controllerPath'];
		return $this->attrsCache['controllerPath']=StringHelper::class2Id(get_class($this));
	}
	
	/**
	 * Путь до папки views
	 * @return mixed|string
	 */
	public function getViewsPath() {
		if (isset($this->attrsCache['viewsPath'])) return $this->attrsCache['viewsPath'];
		$class=($this instanceof HistoryModel)?$this->masterClass:get_class($this);
		return $this->attrsCache['viewsPath']=StringHelper::class2Id($class);
	}
	
	/**
	 * Отрендерить элемент
	 * @param View  $view
	 * @param array $options
	 * @return string
	 */
	public function renderItem(View $view,$options=[]) {
		$path="/{$this->viewsPath}/item";
		if (!is_file($_SERVER['DOCUMENT_ROOT'].'/views'.$path.'.php')) $path='/layouts/item';
		return $view->render(
			$path,
			ArrayHelper::recursiveOverride($options,[
				'model'=>$this,
			])
		);
	}
	
	/**
	 * Отобрать себе все абсорбируемые поля у другой модели этого же класса
	 * @param ArmsModel $model
	 * @param false     $delete
	 * @throws Throwable
	 * @throws Exception
	 * @throws StaleObjectException
	 */
	public function absorbModel(ArmsModel $model, $delete=false) {
		//перебираем аттрибуты-ссылки
		foreach ($this->linksSchema as $attribute=>$schema) {
			//если их нужно отбирать
			if ($this->attributeIsAbsorbable($attribute)) {
				//отбираем
				if ($this->attributeIsReverseLink($attribute))
					$model->attributeReverseLinkRedirect($attribute,$this->id);
				else
					$this->$attribute=$model->$attribute;
			}
		}

		//перебираем аттрибуты-значения
		foreach ($this->attributes as $attribute=>$value) {
			//если их нужно отбирать
			if ($this->attributeIsAbsorbable($attribute)) {
				//отбираем:
				//берем себе значение
				$this->$attribute=$model->$attribute;
				//чистим его у второй модели
				$model->attributeClear($attribute);
			}
		}
		
		if ($delete) {	//если надо удалить ограбленного - удаляем
			$model->delete();
		} else {		//иначе сохраняем его в обомжелом виде
			$model->save();
		}
	}
	
	/**
	 * Чтобы один массив можно было наполнить уникальными моделями разных классов индексируем его по uuid
	 * @param $model
	 * @return string
	 */
	public static function getUUID($model) {
		return get_class($model).'#'.$model->id;
	}
	
	/**
	 * @return string
	 */
	public function uuid() {
		return static::getUUID($this);
	}
	
	/**
	 * Построить ветку(граф) от модели до корня дерева
	 * (для моделей с древовидной связью через атрибут "родитель")
	 * @param object $model модель
	 * @param string $parentAttr аттрибут через который можно получить родителя
	 * @return array
	 */
	public static function buildTreeBranch(object $model, string $parentAttr) {
		$chain=[$model];
		while (is_object($model=$model->$parentAttr)) {
			$chain[]=$model;
		}
		return $chain;
	}
	
	/**
	 * Добавляем наш сценарий валидации с тем же набором атрибутов, что и для основного
	 * @return array|array[]
	 */
	public function scenarios()
	{
		$scenarios=parent::scenarios();
		$scenarios[static::SCENARIO_VALIDATION]=$scenarios[static::SCENARIO_DEFAULT];
		return $scenarios;
	}
	
	public function __get($name)
	{
		//если мы в режиме валидации
		if ($this->getScenario()===static::SCENARIO_VALIDATION) {
			//это значит что в модель "предзагружены" все аттрибуты которые нужны для валидации,
			//но many-2-many геттеры не умеют работать с такими предзагруженными значениями
			//они работают прямо с таблицами из БД, и им пофиг на валидируемые значения
			//проверяем, не пытаемся ли мы открыть загрузчик many-2-many
			if ($link=$this->attributeIsLoader($name)) {
				if (StringHelper::endsWith($link,'_ids'))
					return $this->attributeFetchLinks($link,$this->$link);
			}
		}
		
		
		try {
			return parent::__get($name);
		} catch (UnknownPropertyException $e) {
			//если мы не нашли свойство
			//если это рекурсивный аттрибут, и у модели есть ссылка на родителя
			if (StringHelper::endsWith($name,'Recursive') && $this->canGetProperty($this->parentAttr)) {
				//разбираемся к какому атрибуту у нас добавлена рекурсия
				$plain=StringHelper::removeSuffix($name,'Recursive');
				//и есть ли у нас такой атрибут
				if ($this->canGetProperty($plain)) {
					//выясняем какой у него тип
					$type=$this->getAttributeType($plain);
					
					//для текста мы просто заменяем {{PARENT}} на родительское значение этого же поля
					if ($type==='text')
						return $this->textRecursiveField($plain,$name);
					
					//если аттрибут наследуемый, то вытаскиваем его значение рекурсивно
					if ($this->attributeIsInheritable($plain))
						return $this->findRecursiveAttr($plain,$name);
				}
			}
			throw $e;
		}
	}
	
	/**
	 * Подготавливает запросы для поиска и загрузки списка моделей
	 * @param $columns
	 * @return array
	 */
	public function prepareSearch($columns)
	{
		//для загрузки данных (с пагинацией)
		$query = static::find();
		//для поиска/фильтра (без пагинации, но с возможностью фильтровать по связанным объектам)
		$filter = static::find()->select('DISTINCT('.static::tableName().'.id)');
		if (count($joins=$this->attributesJoins($columns))) {
			$query->with($joins);
			$filter->joinWith($joins);
		};
		return [$query,$filter,$joins];
	}
}
