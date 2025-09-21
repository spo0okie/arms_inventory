<?php

/**
 * Формирование меток и подсказок из единого массива + алиасы
 */

namespace app\models\traits;


use app\components\UrlListWidget;
use app\helpers\ArrayHelper;
use app\helpers\QueryHelper;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use OpenApi\Annotations\Property;
use yii\base\Model;
use yii\base\UnknownPropertyException;

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
				'type' => 'text',
			],
			'history' => ['alias'=>'notepad'],
			'links' => [
				'Ссылки',
				'hint' => UrlListWidget::$hint,
				'type'=>'urls',
			],
			'archived' => [
				'Перенесено в архив',
				'type'=>'boolean',
				'hint' => 'Помечается если в работе более не используется, но для истории запись лучше сохранить',
			],
			'updated_at' => [
				'Время изменения',
				'hint' => 'Дата/время изменения объекта в БД',
				'type' => 'datetime',
				'readOnly' => true
			],
			'updated_by'=>[
				'Редактор',
				'type' => 'string',
				'apiLabel' => 'Автор последних изменений в БД (username)',
				'readOnly' => true
			],
			'external_links' => [
				'Доп. связи',
				'type' => 'json_object',
				'hint' => 'JSON структура с дополнительными объектами и ссылками на внешние информационные системы. '
					.'Хранятся в виде JSON структуры. При записи значений старые узлы структуры объединяются с новыми. '
					.'Запись {"link1":"value1"} изменит во всем наборе ссылок только "link1", остальные останутся без изменений. '
					.'Для удаления элемента из структуры надо записать для него пустое значение, например {"link1":""}. '
					.'Запись пустой строки или пустого JSON {} не меняет никаких значений.',
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
	
	/**
	 * @param $attr
	 * @return string[]|null
	 * @throws \yii\base\UnknownPropertyException
	 */
	public function getAttributeData($attr)
	{
		if (!isset($this->attributeDataCache)) {
			$this->attributeDataCache=$this->attributeData();
		}
		
		if (strpos($attr, '.') !== false) {
			//если аттрибут с точкой, то это ссылка на атрибут связанного объекта
			//вытаскиваем этот объект и его атрибут
			[$model,$attr]=$this->getLinkedAttr($attr);
			return $model->getAttributeData($attr);
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
			//проверяем для рекурсивных аттрибутов somethingRecursive -> something
			if (StringHelper::endsWith($attr,'Recursive')) {
				$simple=substr($attr,0,strlen($attr)-strlen('Recursive'));
				return ($this->getAttributeData($simple));
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
	 * Переопределено, с тем же смыслом, что и выше, чтобы получить не объявленные алиасы
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
	
	/**
	 * Определяет тип атрибута
	 * специальные типы:
	 * - ips, macs, urls - правильно оформленные строки
	 * - link: ссылка на другую модель - надо смотреть в linkSchema, чтобы узнать класс модели
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeType($attribute,$default='string')
	{
		if ($type=$this->getAttributeData($attribute)['type']??false) {
			return $type;
		}
		
		if ($this->attributeIsLink($attribute)) {
			return 'link';
		}

		if ($this->attributeIsLoader($attribute)) {
			return 'link';
		}
		
		if (StringHelper::startsWith($attribute,'is_')) {
			return 'boolean';
		}
		
		switch ($attribute) {
			case 'id': return 'integer';
			case 'ip':
			case 'ips': return 'ips';
			case 'mac':
			case 'macs': return 'macs';
			case 'links':
			case 'urls': return 'urls';
			case 'name':
			case 'comment': return 'string';
			case 'notepad': return 'text';
		}
		
		foreach ($this->rules() as $rule) {
			if (in_array($attribute, (array)$rule[0])) {
				switch ($rule[1]) {
					case 'integer': return 'integer';
					case 'number': return 'number';
					case 'boolean': return 'boolean';
					case 'string': return 'string';
				}
			}
		}
		
		return $default;
	}

	/**
	 * Возвращает наименование атрибута для формы просмотра
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeViewLabel($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (is_array($item) && isset($item['viewLabel']))
			return $item['viewLabel'];
		/** @var $this Model */
		return $this->getAttributeLabel($attribute);
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
		return $this->getAttributeViewLabel($attribute);
	}
	
	/**
	 * Возвращает описание атрибута для формы просмотра
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeViewHint($attribute)
	{
		/** @var $this Model */
		$item=$this->getAttributeData($attribute);
		if (isset($item['viewHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['viewHint']
			);
		if (isset($item['indexHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['indexHint']
			);
		return null;
	}
	
	/**
	 * Возвращает описание атрибута для формы grid
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeIndexHint($attribute)
	{
		/** @var $this Model */
		$item=$this->getAttributeData($attribute);
		if (isset($item['indexHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['indexHint']
			);
		if (isset($item['viewHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['viewHint']
			);
		return null;
	}
	
	/**
	 * Возвращает описание атрибута для формы grid
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeSearchHint($attribute)
	{
		/** @var $this Model */
		$item=$this->getAttributeData($attribute);
		if (isset($item['searchHint']))
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['searchHint']
			);
		switch ($this->getAttributeType($attribute,null)) {
			case 'text':
			case 'ntext':
			case 'string':
			case 'ips':  //по сути тоже просто текст
			case 'macs': //когданить мы родим диапазоны МАКов и для них будет отдельный поиск
			case 'urls': //по сути тоже просто текст
			case 'link': //обычно подразумевает поиск по имени связанных объектов
				return QueryHelper::$stringSearchHint;
			case 'date':
			case 'datetime':
				return QueryHelper::$dateSearchHint;
			case 'number':
				return QueryHelper::$numberSearchHint;
		}
		return '';
	}
	
	
	/**
	 * Возвращает наименование атрибута для API документации
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeApiLabel($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (is_array($item) && isset($item['apiLabel']))
			return $item['apiLabel'];
		/** @var $this Model */
		return $this->getAttributeIndexLabel($attribute);
	}
	
	
	/**
	 * Возвращает описание атрибута для API документации
	 * @param $attribute
	 * @return string
	 */
	public function getAttributeApiHint($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (isset($item['apiHint']))
			/** @var $this Model */
			return str_replace(
				'{same}',
				$this->getAttributeHint($attribute),
				$item['apiHint']
			);
		return $this->getAttributeIndexHint($attribute);
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
	public function renderAttributeToText(string $attr, $glue=', ', $name='name' ) {
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
	
	
	
	/**
	 * Возвращает список джоинов, которые нужно сделать для загрузки атрибута (при использовании joinWith)
	 * @param string $attribute
	 * @return string[]
	 */
	public function getAttributeJoins($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (is_array($item) && isset($item['join'])) {
			$join=$item['join'];
			return is_array($join)?$join:[$join];
		}
		return [];
	}
	
	/**
	 * Возвращает список джоинов, которые нужно сделать для загрузки запрошенных аттрибутов
	 * @param string[]|null $attributes
	 * @return string[]
	 */
	public function attributesJoins($attributes=null) {
		$joins=[];
		//если вместо списка атрибутов передали null, то проверяем все атрибуты
		if (is_null($attributes)) {
			$attributes=array_keys($this->attributeData());
		}
		foreach ($attributes as $attribute) {
			$joins=array_merge($joins,$this->getAttributeJoins($attribute));
		}
		return array_unique($joins);
	}
	
	/**
	 * Возвращает OpenApi\Annotations\Property для атрибута модели
	 * @param $attribute
	 * @return Property
	 */
	public function generateAttributeAnnotation($attribute,$context): Property
	{
		$data=$this->getAttributeData($attribute);
		$name=$this->getAttributeApiLabel($attribute);
		$description='';
		if ($hint=$this->getAttributeApiHint($attribute)) {
			$description.="\n$hint";
		}
		
		$template=[];
		switch ($type=$this->getAttributeType($attribute)) {
			case 'boolean': $template=[
				'type' => 'boolean',
				'format' => 'integer',
				'enum' => [0,1],
				'example' => 1,
			];
				if (!isset($data['fieldList'])) $description.="\n0 - false, 1 - true";
				break;
			
			case 'toggle': $template=[
				'type' => 'integer',
				'format' => 'integer',
				'enum' => [0,1],
				'example' => 1,
			];
				break;
			
			case 'list':
			case 'radios': $template=[
				'type' => 'integer',
				'format' => 'integer',
				'example' => 1,
			];
				break;
			
			case 'text':
			case 'ntext':
			case 'string': $template=[
				'type' => 'string',
			];
				break;
			
			case 'json_object': $template=[
				'type' => 'string',
				'format' => 'json object',
			];
				break;
			
			case 'json_array': $template=[
				'type' => 'string',
				'format' => 'json array',
			];
				break;
			
			case 'date': $template=[
				'type' => 'string',
				'format' => 'date',
				'example' => '2020-01-31',
			];
				break;
				
			case 'datetime': $template=[
				'type' => 'string',
				'format' => 'date-time',
				'example' => '2020-01-31 23:59:59',
			];
				break;
				
			case 'ips': $template=[
				'type' => 'string',
				'example' => '192.168.0.1/24',
			];
				$description.="\n".'IP адреса (опционально с маской подсети). По одному в строке. ';
				break;
				
			case 'macs': $template=[
				'type' => 'string',
				'example' => '00:1A:2B:3C:4D:5E',
			];
				$description.="\n".'MAC адреса. По одному в строке. ';
				break;
				
			case 'urls': $template=[
				'type' => 'string',
				'example' => 'Ссылка на пример https://example.com/some/page',
			];
				$description.="\n".UrlListWidget::$APIhint;
				break;
				
			case 'link':
				$class=StringHelper::className($this->attributeLinkClass($attribute));
				if ($this->attributeIsLoader($attribute)) {
					//если это атрибут загрузчик, то это RO свойство содержащее другой объект
					$template = [
						'type' => 'object',
						'ref' => '#/components/schemas/' . $class,
						'readOnly' => true,
					];
				} else {
					//иначе это сам атрибут ссылка (_id / _ids)
					if (StringHelper::endsWith($attribute, '_ids')) {
						$name .= ' (ссылки)';
						//если множественная ссылка
						$template = [
							'type' => 'array',
							'@items' => [
								'type' => 'integer',
								'example' => 123,
								'description' => "ID объектов $class",
							],
						];
					} elseif (StringHelper::endsWith($attribute, '_id')) {
						$name .= ' (ссылка)';
						$template = [
							'type' => 'integer',
							'example' => 123,
						];
						$description.= "\nID объекта $class";
					}
				}
				break;
			default: $template=[
				'type' => $type,
			];
				break;
		}
		
		if (isset($data['fieldList'])) {
			$fieldList=$data['fieldList'];
			if (is_callable($fieldList)) $fieldList=$fieldList();
			if (is_array($fieldList) && count($fieldList)>0) {
				$template['enum']=array_keys($fieldList);
				$template['example']=array_key_first($fieldList);
				$description.="\nВозможные значения:\n".implode(', ',array_map(function($k,$v){return "$k - $v";},array_keys($fieldList),$fieldList));
			}
		}
		
		if ($template['type']==='string') {
			foreach ($this->rules() as $rule) {
				if (
					in_array($attribute,(array)$rule[0])
				&&
					$rule[1] === 'string'
				&&
					isset($rule['max'])
				) {
					$template['maxLength']=$rule['max'];
				}
			}
		}
		
		if (isset($data['is_inheritable']) && $data['is_inheritable']) {
			$description.="\n".'Атрибут наследуется от родительского объекта, если не задан явно.';
		}
		
		if ($data['readOnly']??false) {
			$template['readOnly']=true;
		}

		if ($data['writeOnly']??false) {
			$template['writeOnly']=true;
		}
		
		if ($description) $name.=": $description";
		$template['property']=$attribute;
		$template['description']=$name;
		$template['_context']=$context;
		
		return new Property($template);
	}
	
}