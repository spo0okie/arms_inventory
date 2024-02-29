<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


use app\helpers\StringHelper;
use app\models\ArmsModel;
use yii\base\Model;

/**
 * Trait ExternalDataModelTrait
 * @package app\models\traits
 * @property string $external_links
 */

trait AttributeDataModelTrait
{
	
	/**
	 * @var array кэш индивидуальных наборов данных
	 */
	protected $attributeDataCache;
	
	/**
	 * @var array кэш лэйблов сформированных из общего набора данных
	 */
	protected $attributeLabelsCache;
	
	
	public function attributeData() {
		return [];
	}
	
	public function getAttributeData($key)
	{
		if (!isset($this->attributeDataCache)) {
			$this->attributeDataCache=$this->attributeData();
		}
		
		if (!isset($this->attributeDataCache[$key])) {
			//проверяем нет ли возможности подменить ссылку на геттер acls_list_ids => aclsList
			if ($getter=StringHelper::linkId2Getter($key)) {
				//если для такого геттера есть данные, то
				if (isset($this->attributeDataCache[$getter])) {
					//сохраняем как ссылку
					$this->attributeDataCache[$key]=['alias'=>$getter];
					//возвращаем данные по геттеру
					return $this->getAttributeData($getter);
				} else {
					$this->attributeDataCache[$key]=null;
				}
			}
			return null;
		}
		
		$data=$this->attributeDataCache[$key];
		if (!isset($data['alias'])) return $data;
		if ($data['alias']==$key) return $data; //no recursion!
		
		return $this->getAttributeData($data['alias']);
	}
	
	/**
	 * Вытаскиваем название аттрибута из данных
	 * @param $data
	 * @return mixed|null
	 */
	public function fetchAttributeLabel($data) {
		if (is_array($data)) {
			if (isset($data[0])) //либо это первый элемент массива
				return $data[0];
			elseif (isset($data['label']))	//либо под конкретным индексом
				return $data['label'];
		}
		return $data;
	}
	
	/**
	 * Генерирует набор меток из массива данных
	 * @return array
	 */
	public function attributeLabels()
	{
		if (is_null($this->attributeLabelsCache)) {
			$this->attributeLabelsCache=[];
			foreach ($this->attributeData() as $key=>$data) {
				$data = $this->getAttributeData($key);
				if ($label = $this->fetchAttributeLabel($data)) {
					$this->attributeLabelsCache[$key] = $label;
				}
			}
		}
		return $this->attributeLabelsCache;
	}
	
	public function fetchAttributeHint($data) {
		if (is_array($data)) {
			if (isset($data[1])) return $data[1];
			if (isset($data['hint'])) return $data['hint'];
		}
		return null;
	}

	/**
	 * Генерирует набор подсказок из массива данных
	 * @return array
	 */
	public function attributeHints()
	{
		$hints=[];
		foreach ($this->attributeData() as $key=>$data) {
			$data=$this->getAttributeData($key);
			if ($hint=$this->fetchAttributeHint($data))	$hints[$key]=$hint;
		}
		return $hints;
	}
	
	/**
	 * Переопределено, для того, чтобы мы могли получить описание аттрибута по динамическим алиасам
	 * т.е. если у нас есть данные для aclsList, но нет данных для acls_list_ids, то мы все равно можем получить данные
	 * для последних, хоть явно их и не задавали
	 * @param $attribute
	 * @return array|mixed|string|null
	 */
	public function getAttributeLabel($attribute) {
		/** @var ArmsModel $this */
		if ($label=$this->fetchAttributeLabel(
			$this->getAttributeData($attribute)
		)) return $label;
		/** @noinspection PhpUndefinedClassInspection */
		return parent::getAttributeLabel($attribute);
	}
	
	/**
	 * Переопределено, с тем же смыслом что и выше, чтобы получить не объявленные алиасы
	 * @param $attribute
	 * @return array|mixed|string|null
	 */
	public function getAttributeHint($attribute) {
		/** @var ArmsModel $this */
		if ($hint=$this->fetchAttributeHint(
			$this->getAttributeData($attribute)
		)) return $hint;
		/** @noinspection PhpUndefinedClassInspection */
		return parent::getAttributeHint($attribute);
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
		/** @var $this Model */
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
			/** @var $this Model */
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['indexHint']
			);
		return null;
	}
	
}