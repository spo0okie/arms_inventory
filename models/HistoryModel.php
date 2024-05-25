<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "arms".
 *
 * @property int $id Идентификатор
 * @property int $master_id Идентификатор
 * @property string $updated_at Время обновления
 * @property string $updated_by Автор обновления
 * @property string $updated_comment  Внешние ссылки
 * @property string $changed_attributes  Внешние ссылки
 
 * @property int $secondsSinceUpdate Секунды с момента обновления
 * @property ArmsModel $masterInstance
 */
class HistoryModel extends ArmsModel
{
	/**
	 * @var string[] Изменение каких полей журнала не считать изменением записи
	 * (если изменения ограничиваются этими полями, то запись не обновляется)
	 */
	public static $ignoreFieldChanges=['id','master_id','updated_at','updated_by','changed_attributes','updated_comment'];
	
	/**
	 * @var string[] Какие поля передавать в связанные журналы при внесении изменений
	 */
	public static $initiatorFields=['updated_at','updated_by','updated_comment'];
	
	
	/**
	 * @var string[] Какие "полезные" атрибуты изменились (сюда не вписываются $ignoreFieldChanges)
	 */
	public $changedAttributes;
	
	/**
	 * @var string Что будет записано в changed_attributes если объект удален
	 */
	public const DELETED_FLAG='object_deleted';

	/**
	 * @return array[] Ссылками на объекты каких классов являются атрибуты
	 * $linksSchema=[
	 * 		'services_ids'=>[
	 * 			Service::class,		//на какой класс ссылаемся
	 * 			'acls_ids',			//если там есть обратная ссылка, то в каком аттрибуте
	 * 		],
	 * 		'user_id'=>[
	 * 			Users::class,
	 * 			'loader'=>'user'	//как загрузчик этого объекта называется в мастер-классе
	 * 		],
	 * ];
	 */
	public function getLinksSchema() {
		if (isset($this->attrsCache['linkSchema'])) return $this->attrsCache['linkSchema'];
		return $this->attrsCache['linkSchema']=$this->masterInstance->linksSchema;
	}
	
	/**
	 * @var string[] Обратный индекс загрузчик => атрибут со ссылками (собирается при инициализации)
	 */
	protected $linksLoaders=[];
	
	/**
	 * @var HistoryModel предыдущая запись в журнале
	 */
	public $previous;
	
	public $masterClass;	//какого класса история
	protected $masterClassInstance;	//экземпляр мастер класса для обращений к не static методам
	
	/**
	 * Получить экземпляр, создать при необходимости
	 * @return ArmsModel
	 */
	public function getMasterInstance() {
		if (!isset($this->masterClassInstance))
			$this->masterClassInstance=new $this->masterClass();
		return $this->masterClassInstance;
	}
	
	public function getHistoryMaster($id) {
		/** @var ArmsModel $masterClass */
		$masterClass=$this->masterClass;
		return $masterClass::findOne($id);
	}
	
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride($this->masterInstance->attributeData(),[
			'updated_comment'=>[
				'Пояснение',
				'indexHint'=>'Пояснение к изменениям',
			]
		]);
	}
	
	public function rules() {
		$attributes=array_keys($this->attributes);
		return [
			//все поля кроме тех что явно для истории по умолчанию null
			[array_diff($attributes,static::$ignoreFieldChanges),'default','value'=>null],
			[$attributes,'safe'],
		];
	}
	
	
	/**
	 * Найти последнюю запись в журнале для известного master_id
	 * @param $master_id
	 * @return array|ActiveRecord|null
	 */
	public static function findLast($master_id) {
		return static::find()
			->where(['master_id'=>$master_id])
			->orderBy(['id'=>SORT_DESC])
			->one();
	}
	
	/**
	 * Найти запись в журнале для известного master_id на период времени
	 * @param $master_id
	 * @param $timestamp
	 * @return array|ActiveRecord|null
	 */
	public static function findOnTimestamp($master_id,$timestamp) {
		return static::find()
			->where(['master_id'=>$master_id])
			->andWhere(['<=','updated_at',$timestamp])
			->orderBy(['id'=>SORT_DESC])
			->limit(1)
			->one();
	}
	
	/**
	 * Упрощает поле для возможности сравнения (если это массив, то сортирует и превращает в строку)
	 * @param $field
	 * @return string
	 */
	public static function simplifyField($field) {
		if (is_array($field)) {
			sort($field);
			return implode(',',$field);
		}
		return $field;
	}
	
	/**
	 * Сравнивает значения полей (предварительно упрощая их, если это массивы)
	 * @param $f1
	 * @param $f2
	 * @return bool
	 */
	public static function compareFields($f1,$f2) {
		return static::simplifyField($f1)==static::simplifyField($f2);
	}
	
	/**
	 * Признак журналирования аттрибута:
	 * что мы ведем его историю, он у нас есть в таблице и его изменения не игнорируются
	 * @param $attr
	 * @return bool
	 */
	public function attributeIsJournaling($attr) {
		//если у нас вообще нет такого атрибута то не журналируем
		//if (!isset($this->attributes[$attr])) return false;
		//если мы его не можем записывать, то не журналируем
		if (!$this->canSetProperty($attr)) return false;
		//если его изменения нас не интересуют, то не журналируем
		if (array_search($attr,static::$ignoreFieldChanges)!==false) return false;
		return true;
	}
	
	/**
	 * Сравнивает запись в журнале с другой записью
	 * @param null $record
	 */
	public function compareRecords($record=null) {
		$this->changedAttributes=[];
		foreach ($this->attributes as $attr=>$value) {
			if (!$this->attributeIsJournaling($attr)) continue;
			$current=$this->$attr;
			$other=$record->$attr;
			if (!static::compareFields($current,$other)) $this->changedAttributes[]=$attr;
		}
	}
	
	/**
	 * Заполняет модель записи журнала значениями исходной модели
	 * @param ArmsModel         $record
	 * @param HistoryModel|null $initiator Кто инициатор изменений (через many2many один объект может менять многие)
	 */
	public function fillRecord(ArmsModel $record, $initiator=null) {
		//$schema=$this->getTableSchema();
		foreach ($this->attributes as $attr=>$value) {
			if (!$this->canSetProperty($attr)) continue;
			//$type=$schema->columns[$attr]->type;
			switch ($attr) {
				case 'master_id':	//ссылка на ID оригинального объекта
					$this->$attr=$record->id;
					break;
				case 'changed_attributes':	//заполняется позже
				case 'id':					//не заполняется
					break;
				default:
					//если у нас есть инициатор и это поле надо брать из него
					if (isset($initiator) && static::isInitiatorAttr($attr)) {
						if (!$record->canGetProperty($attr)) break;
						$value=$initiator->$attr;
					} else {
						//иначе берем из основного объекта
						if (!$record->canGetProperty($attr)) break;
						$value=static::simplifyField($record->$attr);
					}
					//if (is_null($value) && ($type=='text' || $type=='string'))
					//	$value='';
					//грузим в журнал аттрибут
					$this->$attr=$value;
					
			}
		}
	}
	
	
	/**
	 * Является ли поле аттрибутом который надо брать из инициатора если он передан
	 * @param $attr
	 * @return bool
	 */
	public static function isInitiatorAttr($attr) {
		return array_search($attr,static::$initiatorFields)!==false;
	}
	
	/**
	 * Вытаскивает ID связанных объектов из текстового поля
	 * @param $attr
	 * @return array|int
	 */
	public function fetchLinkIds($attr){
		$ids=[];
		if (!isset($this->$attr)||!strlen($this->$attr)) return $ids;
		foreach (explode(',',$this->$attr) as $id)
			$ids[]=(int)$id;
		return $ids;
	}
	
	/**
	 * Грузит предыдущую запись журнала
	 */
	public function loadPrevious() {
		//ищем последнюю запись
		$this->previous=static::findLast($this->master_id);
		
		//если ничего не нашлось - создаем пустую
		if (!isset($this->previous)) $this->previous=new static();
	}
	
	/**
	 * Загрузить связанный объект
	 * @param $attr
	 * @param $id
	 * @return ArmsModel|null
	 */
	public function attributeFetchLink($attr, $id) {
		/** @var ArmsModel $class */
		$class=$this->attributeLinkClass($attr);
		return $class::findOne($id);
	}
	
	/**
	 * Вносит в журнал при необходимости запись
	 * @param ArmsModel    $record	Чье изменение вносим в журнал
	 * @param HistoryModel|null $initiator Кто инициатор изменений (через many2many один объект может менять многие)
	 * @return false
	 */
	public function journal(ArmsModel $record, $initiator=null) {
		//заполняем запись в журнал
		$this->fillRecord($record,$initiator);
		
		//грузим предыдущую запись
		$this->loadPrevious();
		
		//сравниваем
		$this->compareRecords($this->previous);
		
		//ничего важного не изменилось
		if (!count($this->changedAttributes)) return false;
		
		if ($this->hasAttribute('changed_attributes')) {
			$this->changed_attributes=implode(',',$this->changedAttributes);
		}
		
		if ($this->save()) {
			//если инициатор изменений не передан, значит мы и есть инициатор
			// и надо передать информацию об изменениях связанным объектам
			if (!isset($initiator)) $this->spreadOverLinkedJournals($this);
			
			return true;
		}
		return false;
	}
	
	/**
	 * Вносит в журнал запись об удалении объекта
	 * @param ArmsModel    $record	Чье изменение вносим в журнал
	 * @param HistoryModel|null $initiator Кто инициатор изменений (через many2many один объект может менять многие)
	 * @return false
	 */
	public function journalDeletion(ArmsModel $record, $initiator=null) {
		//заполняем запись в журнал
		$this->fillRecord($record,$initiator);
		
		if ($this->hasAttribute('changed_attributes')) {
			$this->changed_attributes=static::DELETED_FLAG;
		}
		
		//все установленные атрибуты считаем измененными чтобы обновились все ссылки которые там могут быть
		$this->changedAttributes=array_keys($this->attributes);
		
		if ($this->save()) {
			//если инициатор изменений не передан, значит мы и есть инициатор
			// и надо передать информацию об изменениях связанным объектам
			if (!isset($initiator)) $this->spreadOverLinkedJournals($this);
			return true;
		}
		return false;
	}
	
	/**
	 * @param HistoryModel $initiator
	 */
	public function spreadOverLinkedJournals(HistoryModel $initiator) {
		//перебрать изменившиеся поля
		foreach ($this->changedAttributes as $attribute) {
			//проверить что он ссылка на объект, который имеет обратную ссылку на нас и журналирует ее изменения
			if (!$this->attributeIsReverseJournaling($attribute)) continue;
			
			// если у нас есть предыдущая запись, то сравниваем значения этого поля чтобы найти что поменялось
			if (is_object($this->previous)) {
				//найти какие ссылки добавились/пропали
				//чтобы найти изменяющиеся позиции надо
				//найти пересечение массивов - не меняющиеся позиции
				//найти объединение массивов - все позиции
				//вычесть пересечение из объединения - только меняющиеся позиции
				$changed = ArrayHelper::setsSymDiff(
					$this->fetchLinkIds($attribute),
					$this->previous->fetchLinkIds($attribute)
				);
			} else {
				//если предыдущая запись не загружена (а при удалении она не загружена)
				//то обрабатываем все ссылки на которые ссылался удаленный объект
				$changed=$this->fetchLinkIds($attribute);
			}
			//загрузить объекты-ссылки
			foreach ($changed as $id) {
				$link=$this->attributeFetchLink($attribute,$id);
				if (is_null($link)) continue; // мало ли
				//вызвать для них historyCommit($initiator)
				$link->historyCommit($initiator);
			}
		}
	}
	
	/**
	 * Найти пользователя из updated_by
	 * @return array|ActiveRecord|null
	 */
	public function getUpdatedByUser() {
		if (!$this->updated_by) return null;
		return Users::find()
			->where(['Login'=>$this->updated_by])
			->one();
	}
	
	/**
	 * Есть ли аттрибут в changed_attributes
	 * @param string $attr
	 * @return bool
	 */
	public function attributeIsChanged(string $attr){
		if (!isset($this->changedAttributes))
			$this->changedAttributes=ArrayHelper::explode(',',$this->changed_attributes);
		
		return in_array($attr,$this->changedAttributes);
	}

	/**
	 * Является ли аттрибут ссылкой
	 * в linksClasses должно быть проставлено на какой класс ссылка
	 * @param string $attr
	 * @return bool
	 */
	public function attributeIsLink(string $attr){
		return isset($this->getLinksSchema()[$attr]);
	}
	
	/**
	 * Схема атрибута ссылки
	 * @param string $attr
	 * @return array
	 */
	public function attributeLinkSchema(string $attr){
		$linkSchema=$this->getLinksSchema();
		if (isset($linkSchema[$attr])) {
			return is_array($linkSchema[$attr])?
				$linkSchema[$attr]:
				[$linkSchema[$attr]];
		}
		return [];
	}
	
	/**
	 * На какой класс ссылается аттрибут
	 * @param string $attr аттрибут
	 * @return string
	 */
	public function attributeLinkClass(string $attr) {
		return $this->attributeLinkSchema($attr)[0];	//первым элементом всегда идет класс
	}
	
	/**
	 * Какой атрибут объекта-ссылки ссылается обратно на нас
	 * @param string $attr
	 * @return array|false|mixed
	 */
	public function attributeReverseLink(string $attr) {
		$schema=$this->attributeLinkSchema($attr);
		if (isset($schema[1])) return $schema[1]; //вторым элементом всегда идет обратная ссылка
		if (isset($schema['reverseLink'])) return $schema['reverseLink']; //либо так
		return false;
	}
	
	/**
	 * Как называется getter в мастер классе, который загружает объекты-ссылки
	 * @param string $attr
	 * @return string|false
	 */
	public function attributeLinkLoader(string $attr) {
		if (!$this->attributeIsLink($attr)) return false;
		
		$schema=$this->attributeLinkSchema($attr);
		if (isset($schema['loader'])) return $schema['loader']; //если указан то и славно
		
		if ($loader=StringHelper::linkId2Getter($attr)) return $loader;
		
		return false;
	}
	
	
	/**
	 * Атрибут является ссылкой на объект, который журналирует обратную ссылку на этот объект.
	 * Т.е. если у нас этот аттрибут изменился, то нужно будет пнуть все добавленные и удаленные из него объекты
	 * на предмет журналирования изменений.
	 * @param string $attr
	 * @return bool
	 */
	public function attributeIsReverseJournaling(string $attr) {
		if (!$this->attributeIsLink($attr)) return false;
		
		//выясним, есть ли обратная ссылка у этого аттрибута
		$reverseLinkAttr=$this->attributeReverseLink($attr);
		//если нет, то нечего журналировать
		if (!$reverseLinkAttr) return false;

		//подтянем класс
		/** @var ArmsModel $class */
		$class=$this->attributeLinkClass($attr);
		$instance=new $class();
		$historyClass=$instance->getHistoryClass();
		
		//если у нас этот класс не ведет историю, то и нечего журналировать
		if (!$historyClass) return false;
		
		
		/** @var HistoryModel $journal */
		$journal=new $historyClass();
		
		//у нас есть класс истории и есть аттрибут-обратная ссылка. Возвращаем ведется ли его журнал
		return $journal->attributeIsJournaling($reverseLinkAttr);
	}
	
	
	
	/**
	 * Получить объекты, на которые ссылается аттрибут
	 * @param string $attr
	 * @return ActiveRecord[]|ActiveRecord
	 */
	public function fetchLinks(string $attr){
		/** @var ArmsModel $class */
		$class=$this->attributeLinkClass($attr);
		
		//вариант со ссылкой на один объект
		if (substr($attr,strlen($attr)-3)=='_id') {
			return $class::fetchJournalRecord($this->$attr,$this->updated_at);
		}
		
		//иначе грузим пачку
		$models=[];
		foreach (explode(',',$this->$attr??'') as $id) {
			if (is_object($model=$class::fetchJournalRecord($id,$this->updated_at))) {
				$models[]=$model;
			}
		};
		return $models;
	}
	
	/**
	 * Перекрываем getter аттрибутов, чтобы эмулировать параметры мастер-класса
	 * Идея такая:
	 * мы просим атрибут serviceUser, у нас такого нет
	 * но мы знаем что это getter для атрибута service_user_id
	 * тогда мы просто грузим нужный объект и отдаем, (как будто у нас есть такой атрибут)
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		// если мы знаем что такой геттер в мастер классе хранит свои ссылки в другом аттрибуте, то грузим ссылки
		// например геттер serviceUser хранит ссылки в service_user_id
		if (isset($this->linksLoaders[$name])) {
			return $this->fetchLinks($this->linksLoaders[$name]);
		}
		
		return parent::__get($name);
	}
	
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		
		//строим кэша обратного индекса загрузчиков атрибутов
		foreach ($this->attributes as $attr=>$value) {
			//ищем загрузчик аттрибута
			$loader = $this->attributeLinkLoader($attr);
			//если нашелся - запоминаем пару загрузчик-атрибут
			if ($loader) $this->linksLoaders[$loader] = $attr;
		}
		
		$this->trigger(self::EVENT_INIT);
	}
}
