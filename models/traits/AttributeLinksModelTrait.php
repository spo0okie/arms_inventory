<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


use app\helpers\StringHelper;
use app\models\ArmsModel;
use voskobovich\linker\LinkerBehavior;

/**
 * Trait ExternalDataModelTrait
 * @package app\models\traits
 * @property string $external_links
 */

trait AttributeLinksModelTrait
{
	/**
	 * @var array схема связей моделей через ссылки
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
	public $linksSchema=[];
	
	public function getLinksSchema() {
		return $this->linksSchema;
	}
	
	/**
	 * @var string[] Обратный индекс загрузчик => атрибут со ссылками (собирается при инициализации)
	 */
	protected $linksLoaders=[];
	
	/**
	 * @var array Кэш флага атрибута "обратная ссылка"
	 */
	protected $reverseLinksCache=[];
	
	
	/**
	 * В списке поведений прикручиваем many-to-many контрагентов
	 * @return array
	 */
	public function relationsBehaviour()
	{
		$relations=[];
		
		foreach ($this->getLinksSchema() as $attribute=>$data) {
			if (StringHelper::endsWith($attribute,'_ids')) {
				if ($loader=$this->attributeLinkLoader($attribute)) {
					$relations[$attribute]=$loader;
				}
			}
		}
		
		return	[
			'class' => LinkerBehavior::class,
			'relations' => $relations,
		];
	}
	
	/**
	 * Признак того что исходя из схемы ссылок атрибут является обратной ссылкой
	 * (множество объектов указанных в этом аттрибуте ссылаются на него)
	 * many-2-one, many-2-many
	 * @param $attr
	 * @return bool
	 */
	public function attributeIsReverseLink($attr) {
		if (isset($this->reverseLinksCache[$attr]))
			return $this->reverseLinksCache[$attr];
		
		$data=$this->getAttributeData($attr);
		if (isset($data['is_reverseLink'])) {
			return $this->reverseLinksCache[$attr]=$data['is_reverseLink'];
		}
		
		//если аттрибут выглядит как ссылка на несколько объектов
		//(ссылка на один может иметь обратную ссылку, но тогда это one-2-many, и это можно удалять без последствий)
		if (StringHelper::endsWith($attr,'_ids')) {
			//если там есть обратная ссылка (хоть какая, с этой то стороны точно множественная)
			if ($this->attributeReverseLink($attr)) {
				return $this->reverseLinksCache[$attr]=true;
			}
		}
		return $this->reverseLinksCache[$attr]=false;
	}
	
	/**
	 * Возвращает ссылки на объекты ссылающиеся на этот
	 * по схеме one-to-many и many-to-many
	 * @return array
	 */
	public function reverseLinks() {
		$links=[];
		foreach ($this->getLinksSchema() as $attribute=>$data) {
			if ($this->attributeIsReverseLink($attribute)) {
				if ($loader=$this->attributeLinkLoader($attribute)) {
					$links[]=$this->$loader;
				};
			}
		}
		return $links;
	}
	
	
	
	/**
	 * Загрузить связанный объект
	 * @param $attr
	 * @param $id
	 * @return ArmsModel|null
	 */
	public function attributeFetchLink($attr,$id) {
		/** @var ArmsModel $class */
		$class=$this->attributeLinkClass($attr);
		return $class::findOne($id);
	}
	
	public function attributeFetchLinks($attr,$ids) {
		/** @var ArmsModel $class */
		$class=$this->attributeLinkClass($attr);
		return $class::findAll($ids);
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
	 * Как называется getter в классе, который загружает объекты-ссылки
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
	 * Перечислить имена объектов на которые ссылается аттрибут
	 * @param string $attr что за аттрибут
	 * @param string $glue как соединить несколько значений
	 * @param string $name какой аттрибут у объектов использовать как имя
	 * @return string
	 */
	public function renderAttributeLinkToText(string $attr,$glue=',',$name='name') {
		//грузим ид на что ссылаемся
		$ids=$this->$attr;
		//если пусто, то пусто
		if (empty($ids)) return '';
		//для единообразия работаем с массивом ссылок
		if (!is_array($ids)) $ids=[$ids];
		
		$models=$this->attributeFetchLinks($attr,$ids);
		$names=[];
		foreach ($models as $model) $names[]=$model->$name;
		sort($names);
		return implode($glue,$names);
	}
	
	
	/**
	 * Поменять в атрибуте ссылку с одного объекта на другой
	 * @param string  $attr Поле-ссылка (user_ids,comp_id,service_id,...)
	 * @param integer $old_id Значение которое надо изменить
	 * @param integer $new_id На какое значение
	 * @return boolean
	 */
	public function attributeLinkRedirect(string $attr, int $old_id, int $new_id) {
		if (is_array($this->$attr)) {	//если поле - массив ссылок
			$this->$attr=array_merge(array_diff($this->$attr,[$old_id]),[$new_id]);
		} else {
			$this->$attr=$new_id;
		}
		return $this->save();
	}
	
	/**
	 * Перенаправить атрибут обратную-ссылку с себя на другой объект
	 * Например если какие-то ACL ссылаются на меня, то в этих ссылках поменять себя на другой объект
	 * @param string $attr Атрибут, который надо перенаправить
	 * @param int $new_id
	 */
	public function attributeReverseLinkRedirect(string $attr, int $new_id) {
		$loader=$this->attributeLinkLoader($attr);			//как нам загрузить объекты ссылающиеся на нас
		$reverseLink=$this->attributeReverseLink($attr);	//в каком поле у этих объектов ссылка на нас
		$linkingObjects=$this->$loader;						//грузим все объекты
		foreach ($linkingObjects as $object) {
			/** @var ArmsModel $object */
			//перенаправляем там нужный аттрибут на новый ID
			$object->attributeLinkRedirect($reverseLink,$this->id,$new_id);
		}
	}
}