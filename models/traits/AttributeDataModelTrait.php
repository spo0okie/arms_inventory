<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


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