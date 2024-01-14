<?php

namespace app\models;

use app\helpers\ArrayHelper;
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
	public $changedAttributes=[];
	
	/**
	 * @var string[] Пояснение какие поля являются many2many полями и каких классов ['services_ids'=>Service::class,]
	 */
	public static $journalMany2ManyLinks=[];
	
	/**
	 * @var HistoryModel предыдущая запись в журнале
	 */
	public $previous;
	
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
	 * Упрощает поле для возможности сравнения
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
	 * Сравнивает значения полей предварительно упрощая их
	 * @param $f1
	 * @param $f2
	 * @return bool
	 */
	public static function compareFields($f1,$f2) {
		return static::simplifyField($f1)==static::simplifyField($f2);
	}
	
	/**
	 * Сравнивает запись в журнале с другой записью
	 * @param null $record
	 */
	public function compareRecords($record=null) {
		foreach ($this->attributes as $attr=>$value) {
			if (!$this->canSetProperty($attr)) continue;
			if (array_search($attr,static::$ignoreFieldChanges)!==false) continue;
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
		foreach ($this->attributes as $attr=>$value) {
			if (!$this->canSetProperty($attr)) continue;
			switch ($attr) {
				case 'master_id':	//ссылка на ID оригинального объекта
					$this->$attr=$record->id;
					break;
				case 'changed_attributes':	//заполняется позже
				case 'id':					//не заполняется
					continue;
				default:
					//если у нас есть инициатор и это поле надо брать из него
					if (isset($initiator) && static::isInitiatorAttr($attr)) {
						if (!$record->canGetProperty($attr)) continue;
						$value=$initiator->$attr;
					} else {
						//иначе берез из основного объекта
						if (!$record->canGetProperty($attr)) continue;
						$value=static::simplifyField($record->$attr);
					}
					//грузим в журнал аттрибут
					$this->$attr=$value;
			}
		}
	}
	
	/**
	 * Является ли поле ссылкой Many2Many
	 * @param $attr
	 * @return bool
	 */
	public static function isMany2ManyLink($attr) {
		return isset(static::$journalMany2ManyLinks[$attr]);
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
	public function fetchMany2ManyIds($attr){
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
	public function fetchLink($attr,$id) {
		/** @var ArmsModel $class */
		$class=static::$journalMany2ManyLinks[$attr];
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
	 * @param HistoryModel $initiator
	 */
	public function spreadOverLinkedJournals(HistoryModel $initiator) {
		//перебрать изменившиеся поля
		foreach ($this->changedAttributes as $attribute) {
			//выбрать из них many2many
			if (!static::isMany2ManyLink($attribute)) continue; //пропускаем поля "не m-2-m ссылки"
			
			//найти какие ссылки добавились/пропали
			//чтобы найти изменяющиеся позиции надо
			//найти пересечение массивов - не меняющиеся позиции
			//найти объединение массивов - все позиции
			//вычесть пересечение из объединения - только меняющиеся позиции
			$changed=ArrayHelper::setsSymDiff(
				$this->fetchMany2ManyIds($attribute),
				$this->previous->fetchMany2ManyIds($attribute)
			);
			//загрузить объекты-ссылки
			foreach ($changed as $id) {
				$link=static::fetchLink($attribute,$id);
				if (is_null($link)) continue; // мало ли
				//вызвать для них historyCommit($initiator)
				$link->historyCommit($initiator);
			}
		}
	}
}
