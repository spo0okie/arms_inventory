<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


use app\components\UrlListWidget;
use app\helpers\ArrayHelper;
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
	/** @var string как формировать плейсхолдер для наследуемых аттрибутов */
	public $inheritableAttrPlaceholderTemplate='{parentValue} (унаследовано)';
	
	/** @var string в каком аттрибуте у нас предок */
	public $parentAttr='parent';
	
	/**
	 * @var array кэш индивидуальных наборов данных
	 */
	protected $attributeDataCache;
	
	/**
	 * @var array кэш лэйблов сформированных из общего набора данных
	 */
	protected $attributeLabelsCache;
	
	
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
				'type' => 'text'
			],
			'history' => ['alias'=>'notepad'],
			'links' => [
				'Ссылки',
				'hint' => UrlListWidget::$hint,
			],
			'archived' => [
				'Перенесено в архив',
				'hint' => 'Помечается если в работе более не используется, но для истории запись лучше сохранить',
			],
			'updated_at' => [
				'Время изменения',
				'hint' => 'Дата/время изменения объекта в БД',
			],
			'updated_by'=>[
				'Редактор',
				'hint' => 'Автор последних изменений объекта',
			],
			'external_links' => [
				'Доп. связи',
				'hint' => 'JSON структура с дополнительными объектами и ссылками на внешние информационные системы',
			],
		];
	}
	
	/**
	 * Является ли аттрибут наследуемым (от объекта родителя)
	 * @param $attr
	 * @return false|mixed
	 */
	public function attributeIsInheritable($attr) {
		return $this->getAttributeData($attr)['is_inheritable']??false;
	}
	
	/**
	 * Является ли аттрибут наследуемым (от объекта родителя)
	 * @param $attr
	 * @return false|mixed
	 */
	public function attributeIsAbsorbable($attr) {
		$data=$this->getAttributeData($attr);
		//если явно указано какой атрибут
		if (isset($data['absorb'])) {
			//если указано импортировать только поверх пустого
			if ($data['absorb']=='ifEmpty') {
				return empty($this->$attr);
			}
			//возвращаем что указано
			return $data['absorb'];
		}
		//обратные ссылки по умолчанию надо отбирать, остальное нет
		return $this->attributeIsReverseLink($attr);
	}
	
	public function getAttributeData($attr)
	{
		if (!isset($this->attributeDataCache)) {
			$this->attributeDataCache=$this->attributeData();
		}
		
		if (!isset($this->attributeDataCache[$attr])) {
			//проверяем нет ли возможности подменить ссылку на геттер acls_list_ids => aclsList
			if ($getter=StringHelper::linkId2Getter($attr)) {
				//если для такого геттера есть данные, то
				if (isset($this->attributeDataCache[$getter])) {
					//сохраняем как ссылку
					$this->attributeDataCache[$attr]=['alias'=>$getter];
					//возвращаем данные по геттеру
					return $this->getAttributeData($getter);
				}
			}
			//проверяем в обратную сторону aclsList => acls_list_ids
			if ($link=$this->attributeIsLoader($attr)) {
				//если есть данные для исходного аттрибута
				if (isset($this->attributeDataCache[$link])) {
					//сохраняем как ссылку
					$this->attributeDataCache[$attr]=['alias'=>$link];
					//возвращаем данные по исходному аттрибуту
					return $this->getAttributeData($link);
				}
			}
			$this->attributeDataCache[$attr]=null;
			return null;
		}
		
		$data=$this->attributeDataCache[$attr];
		if (!isset($data['alias'])) return $data;
		if ($data['alias']==$attr) return $data; //no recursion!
		
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
			else return null;
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
			foreach ($this->getLinksSchema() as $key=>$data) {
				if (isset($this->attributeLabelsCache[$key])) continue;
				$class=$this->attributeLinkClass($key);
				if (StringHelper::endsWith($key,'_id')) {
					if (property_exists($class,'title'))
						$this->attributeLabelsCache[$key] = $class::$title;
				}
				
				if (StringHelper::endsWith($key,'_ids')) {
					if (property_exists($class,'titles'))
						$this->attributeLabelsCache[$key] = $class::$titles;
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
		return parent::getAttributeHint($attribute);
	}
	
	public function getAttributeType($attribute)
	{
		if ($type=$this->getAttributeData($attribute)['type']??false) {
			return $type;
		}
		if ($this->attributeIsLink($attribute)) {
			return 'link';
		}
		
		if (StringHelper::startsWith($attribute,'is_')) {
			return 'boolean';
		}
		
		switch ($attribute) {
			case 'ips': return 'ips';
			case 'macs': return 'macs';
			case 'links':
			case 'urls': return 'urls';
		}
		
		return 'string';
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
	
	/**
	 * Вернуть локальный плейсхолдер без учета предков
	 * @param $attr
	 * @return string;
	 */
	public function getAttributeLocalPlaceholder($attr) {
		$data=$this->getAttributeData($attr);
		$placeholder=ArrayHelper::getField($data,'placeholder','');
		if (is_callable($placeholder))
			return $placeholder();
		else
			return $placeholder;
	}
	
	/**
	 * Возвращает плейхолдер только для наследуемых полей
	 * @param string $attr что за аттрибут
	 * @param string $glue как соединить несколько значений
	 * @param string $name какой аттрибут у объектов использовать как имя
	 * @return string
	 */
	public function getAttributeInheritablePlaceholder(string $attr,$glue=', ',$name='name') {
		$selfValue=$this->$attr;
		$value=$this->findRecursiveAttr($attr);
		//
		if (!empty($selfValue)||empty($value)) return $this->getAttributeLocalPlaceholder($attr);

		$renderedValue=$this->renderAttributeToText($attr,$glue,$name);
		return str_replace('{parentValue}',$renderedValue,$this->inheritableAttrPlaceholderTemplate);
	}
	
	/**
	 * Возвращает плейсхолдер для ввода аттрибута (в форме) независимо от типа аттрибута (наследуемый/нет)
	 * @param $attr
	 * @return string
	 */
	public function getAttributePlaceholder($attr) {
		if (!$this->attributeIsInheritable($attr)){
			return $this->getAttributeLocalPlaceholder($attr);
		} else {
			$data=$this->getAttributeData($attr);
			$name=ArrayHelper::getField($data,'placeholderValueName','name');
			$glue=ArrayHelper::getField($data,'placeholderValueGlue',', ');
			if (isset($data['inheritablePlaceholder'])) {
				$placeholder=$data['inheritablePlaceholder'];
				if (is_callable($placeholder))
					return $placeholder($attr,$glue,$name);
				else
					return $placeholder;
			}
			return $this->getAttributeInheritablePlaceholder($attr,$glue,$name);
		}
	}
	
	/**
	 * Вернуть только те аттрибуты, у которых динамический плейсхолдер
	 * @return array
	 */
	public function getDynamicPlaceholdersAttrs() {
		if (isset($this->attrsCache['dynamicPlaceholdersAttrs']))
			return $this->attrsCache['dynamicPlaceholdersAttrs'];
		
		$attrs=$this->attributeData();
		$dynamic=[];
		foreach ($attrs as $attr=>$data) {
			$is_inheritable=$data['is_inheritable']??false;
			$placeholder=$data['placeholder']??false;
			$is_function=is_callable($placeholder);
			if ($is_function || $is_inheritable) {
				$dynamic[]=$attr;
			}
		}
		
		return $this->attrsCache['dynamicPlaceholdersAttrs']=$dynamic;
	}
	
	/**
	 * Возвращает список всех динамических(вычисляемых) плейсхолдеров в виде ['attr'=>'placeholder text'];
	 * @return array
	 */
	public function getDynamicPlaceholders() {
		$placeholders=[];
		foreach ($this->getDynamicPlaceholdersAttrs() as $attr)
			$placeholders[$attr]=$this->getAttributePlaceholder($attr);
		return $placeholders;
	}
	
	/**
	 * очищает атрибут $attr
	 * смотрит в схему, чтобы понимать может ли быть атрибут быть NULL
	 * если может то ставит NULL
	 * если не может, то строковые значения очищает
	 * @param $attr
	 */
	public function attributeClear($attr) {
		$column=static::getTableSchema()->getColumn($attr);
		
		if ($column->allowNull) {
			$this->$attr=null;
			return;
		}
		
		if ($column->type=='string') {
			$this->$attr='';
			return;
		}
	}
	
	/**
	 * Текстовый вид аттрибута (в случае объектов - имена через запятую)
	 * @param string $attr что за аттрибут
	 * @param string $glue как соединить несколько значений
	 * @param string $name какой аттрибут у объектов использовать как имя
	 * @return string
	 */
	public function renderAttributeToText(string $attr,$glue=', ',$name='name') {
		$value=$this->$attr;

		if (empty($value) && $this->attributeIsInheritable($attr)) {
			$parentAttr=$this->parentAttr;
			$parent=$this->$parentAttr;
			if (is_object($parent)) return $parent->renderAttributeToText($attr,$glue,$name);
		}
		
		if ($this->attributeIsLink($attr))
			return $this->renderAttributeLinkToText($attr,$glue,$name);
		else {
			if (is_array($value)) {
				$model=reset($value);
				if (is_object($model)) {
					return implode($glue,ArrayHelper::getArrayField($value,'name'));
				} else
					return implode($glue,$value);
			} else
				return (string)($this->$attr);
		}
	}
	
}