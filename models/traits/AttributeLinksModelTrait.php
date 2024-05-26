<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


use app\helpers\StringHelper;
use app\models\ArmsModel;

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
}