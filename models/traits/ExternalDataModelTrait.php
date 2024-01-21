<?php
/**
 * Работа с полем-JSON структурой
 */

namespace app\models\traits;


use app\helpers\ArrayHelper;
use app\models\ArmsModel;

/**
 * Trait ExternalDataModelTrait
 * @package app\models\traits
 * @property string $external_links
 */

trait ExternalDataModelTrait
{
	
	/**
	 * @var array сюда складываем расшифрованную JSON структуру
	 */
	protected $externalDataArray;
	
	/**
	 * Массив описания полей
	 */
	public function externalAttributeData()
	{
		return [
			'external_links' => [
				'Доп. связи',
				'hint' => 'JSON структура с дополнительными объектами и ссылками на внешние информационные системы',
			]
		];
	}
	
	/**
	 * Расшифровать JSON
	 */
	public function externalDataDecode() {
		$this->externalDataArray=$this->external_links?json_decode($this->external_links,true):[];
	}
	
	/**
	 * Зашифровать JSON
	 */
	public function externalDataEncode() {
		$this->external_links=json_encode($this->externalDataArray,JSON_UNESCAPED_UNICODE);
	}
	
	/**
	 * Получить JSON Данные в виде массива (на лету или из кэша)
	 * @return array
	 */
	public function getExternalData() {
		if (!isset($this->externalDataArray)) $this->externalDataDecode();
		return $this->externalDataArray;
	}
	
	/**
	 * Получить из структуры внешних данных элемент по заданному пути
	 * @param array $path путь ['options','pluginOptions','multiple']
	 * @param null  $default значение если элемент не найден
	 * @return array|mixed|null
	 */
	public function getExternalItem(array $path,$default=null) {
		return ArrayHelper::getTreeValue($this->getExternalData(),$path,$default);
	}
	
	/**
	 * Задать в структуре внешних данных элемент по заданному пути
	 * @param array $path путь ['options','pluginOptions','multiple']
	 * @param mixed $value значение элемента
	 */
	public function setExternalItem(array $path,$value) {
		$this->externalDataArray=ArrayHelper::setTreeValue(
			$this->getExternalData(),
			$path,
			$value
		);
	}
	
	
	
	public function externalDataBeforeSave() {
		// Механизм обновления поля external_links такой что задавая какую-то внешнюю ссылку
		// - она просто добавляется к существующим
		/** @var ArmsModel $previous */
		$previous=static::findOne($this->id);
		if (!is_object($previous)) return;
		
		$merged=ArrayHelper::recursiveOverride(
			$previous->getExternalData(),
			$this->getExternalData()
		);
		
		$this->externalDataArray=$merged;
		$this->externalDataEncode();
	}
	

}