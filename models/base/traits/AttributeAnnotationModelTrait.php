<?php

/**
 * Trait AttributeAnnotationModelTrait
 *
 * Методы для генерации аннотаций атрибутов модели для OpenAPI документации
 */

namespace app\models\base\traits;

use app\components\UrlListWidget;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\base\Model;
use yii\base\UnknownPropertyException;

trait AttributeAnnotationModelTrait
{
	
	private static int $intSample=1;
	
	/**
	 * Возвращает пример значения атрибута для API документации
	 * @param string     $attribute
	 * @param mixed|null $default
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeApiExample(string $attribute, mixed $default=null)
	{
		$item=$this->getAttributeData($attribute);
		//если пример описан в метаданных, то возвращаем его
		if (isset($item['example']))
			return $item['example'];
		
		return $default;
	}
	
	/**
	 * Возвращает наименование атрибута для API документации
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
	 */
	public function getAttributeApiLabel($attribute)
	{
		$item=$this->getAttributeData($attribute);
		if (is_array($item) && isset($item['apiLabel']))
			return $item['apiLabel'];
		
		return $this->getAttributeLabel($attribute);
	}
	
	
	/**
	 * Возвращает описание атрибута для API документации
	 * @param $attribute
	 * @return string
	 * @throws UnknownPropertyException
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
			
		if ($hint=$this->getAttributeViewHint($attribute))
			return $hint;
	
		return $this->getAttributeHint($attribute);
	}
	
	/**
	 * Возвращает OpenApi\Annotations\Property для описания свойств метода чтения модели
	 * @param $attribute
	 * @return array
	 * @throws UnknownPropertyException
	 */
	public function generateRWAttributeAnnotation($attribute): array
	{
		$delimiter="<br>";
		/** @var \app\models\base\ArmsModel $this */
		$data=$this->getAttributeData($attribute);
		$name=$this->getAttributeApiLabel($attribute);
		$hint=$this->getAttributeApiHint($attribute);

		$template=[];
		$descriptionRead=[];
		$descriptionWrite=[];
		if ($hint) {
			$descriptionRead[]=$hint;
			$descriptionWrite[]=$hint;
		}

		if ($ref=$data['ref']??false) {
			//read-only вычисляемая ссылка (категория C): только вывод, без ввода/валидации/генерации.
			//Зеркалит loader-ветку ссылок (object / array-of-object), но не является схемной связью.
			$class=StringHelper::className($ref);
			if ($data['refMulti']??false) {
				$template=['type'=>'array','items'=>['type'=>'object']];
				$descriptionRead[]="Массив объектов $class (см. соответствующую схему)";
			} else {
				$template=['type'=>'object'];
				$descriptionRead[]="Объект $class (см. соответствующую схему)";
			}
		} elseif ($this->attributeIsLink($attribute) || $this->attributeIsLoader($attribute)) {
			//структурная ссылка (linksSchema): object / array-of-object / id
			if ($link=$this->attributeIsLoader($attribute)) {

				$class=StringHelper::className($this->attributeLinkClass($link));
				//если это атрибут загрузчик, то это RO свойство содержащее другой объект
				if (StringHelper::endsWith($link, '_ids')) {
					$template=[
						'type' => 'array',
						'items' => ['type' => 'object',],
					];
					$descriptionRead[]="Массив объектов $class (см. соответствующую схему)";
				} else {
					$template=[
						'type' => 'object',
					];
					$descriptionRead[]="Объект $class (см. соответствующую схему)";
				}

			} else {
				$class=StringHelper::className($this->attributeLinkClass($attribute));
				//иначе это сам атрибут ссылка (_id / _ids)
				if (StringHelper::endsWith($attribute, '_ids')) {
					$name .= ' (IDs)';
					//если множественная ссылка
					$template = [
						'type' => 'array',
						'items' => ['type' => 'integer',],
						'example' => [static::$intSample++,static::$intSample++],
					];
					$descriptionRead[]="Массив идентификаторов (ID) объектов класса $class";
				} elseif (StringHelper::endsWith($attribute, '_id')) {
					$name .= ' (ID)';
					$template = [
						'type' => 'integer',
						'example' => static::$intSample++,
					];
					$descriptionRead[]="Идентификатор (ID) объекта класса $class";
				}
			}
		} else {
			//База типа/формата — единый источник истины через apiSchema() класса-типа.
			//СТРОГО: бросает исключение, если тип не выводится из объявления/правил (без fallback),
			//чтобы тесты заставили объявить typeClass явно там, где он не выводится алгоритмически.
			$tc=$this->getAttributeTypeClass($attribute);
			$template=$tc->apiSchema();
			$type=$tc::name();	//тип для декораций ниже (заменяет getAttributeType)
			//Декорации поверх базовой схемы (примеры/enum/описания, которые несла старая switch-ветка):
			switch ($type) {
				case 'boolean':
				case 'toggle':
					$template['format']='integer';
					$template['enum']=[0,1];
					$template['example']=$this->getAttributeApiExample($attribute,1);
					if (!isset($data['fieldList']) && $type==='boolean') {
						$descriptionRead[]="0 - false, 1 - true";
						$descriptionWrite[]="0 - false, 1 - true";
					}
					break;
				case 'list':
				case 'radios':
					$template=[
						'type' => 'integer',
						'format' => 'integer',
						'example' => static::$intSample++
					];
					break;
				case 'date':
					$template['example']='2020-01-31';
					break;
				case 'datetime':
					$template['example']='2020-01-31 23:59:59';
					break;
				case 'ip':
				case 'ipNet':
				case 'ips':
					$template['example']='192.168.0.1/24';
					$descriptionRead[]='IP адреса разделенные переносом строки.';
					$descriptionWrite[]='IP адреса (опционально с маской подсети). По одному в строке.';
					break;
				case 'macs':
					$template['example']='00:1A:2B:3C:4D:5E';
					$descriptionRead[]='MAC адреса. По одному в строке.';
					$descriptionWrite[]='MAC адреса. По одному в строке.';
					break;
				case 'urls':
					$template['example']='Ссылка на пример https://example.com/some/page';
					$descriptionRead[]=UrlListWidget::$APIhint;
					$descriptionWrite[]=UrlListWidget::$APIhint;
					break;
			}
		}

		//если в метаданных есть пример, то используем его, а не тот что сгенерили по умолчанию
		if ($example=$this->getAttributeApiExample($attribute))
			$template['example']=$example;
		
		//если в метаданных есть список значений, то добавляем его в аннотацию
		if (isset($data['fieldList'])) {
			$fieldList=$data['fieldList'];
			if (is_callable($fieldList)) $fieldList=$fieldList();
			if (is_array($fieldList) && count($fieldList)>0) {
				$template['enum']=array_keys($fieldList);
				$template['example']=array_key_first($fieldList);
				$listDescription="Возможные значения:"
					.implode(', ',array_map(function($k,$v){return "$k - $v";},array_keys($fieldList),$fieldList));
				$descriptionWrite[]=$listDescription;
				$descriptionRead[]=$listDescription;
			}
		}
		
		//если строка, то смотрим на правила валидации на предмет ограничения длины
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
			$descriptionRead[]='Атрибут наследуется от родительского объекта, если не задан явно.';
		}
		
		
		$validators=$this->getActiveValidators($attribute);
		if (empty($validators)) {
			$template['readOnly']=true;
		}

		if ($data['readOnly']??false) {
			$template['readOnly']=true;
		}

		if ($data['writeOnly']??false) {
			$template['writeOnly']=true;
		}
		
		if ($this->attributeIsExtra($attribute)) {
			$descriptionRead[]="ДОПОЛНИТЕЛЬНОЕ ПОЛЕ: нужно запрашивать явно через параметр expand";
		}
		
		$template['property']=$attribute;
		$template['descriptionRead']=$name;
			if (count($descriptionRead)) $template['descriptionRead'].=': '.implode($delimiter,$descriptionRead);
		$template['descriptionWrite']=$name;
			if (count($descriptionWrite))$template['descriptionWrite'].=': '.implode($delimiter,$descriptionWrite);
		
		return $template;
	}

	/**
		* Возвращает OpenApi\Annotations\Parameter для атрибута модели в качестве параметра поиска
		* @param $attribute
		* @param $context
		* @return \OpenApi\Annotations\Parameter
		* @throws \yii\base\UnknownPropertyException
		*/
	public function generateSearchParameterAnnotation($attribute,$context): \OpenApi\Annotations\Parameter
	{
		$delimiter="; ";
		/** @var \app\models\base\ArmsModel $this */
		$data=$this->getAttributeData($attribute);
		$name=$this->getAttributeApiLabel($attribute);
		$hint=$this->getAttributeApiHint($attribute);

		$description=$hint??'';
		$schemaTemplate=[];

		if ($ref=$data['ref']??false) {
			//read-only вычисляемая ссылка (категория C): поиск по имени связанного объекта
			$class=StringHelper::className($ref);
			$schemaTemplate=['type'=>'string'];
			$description=StringHelper::appendToDelimitedString(
				$description,$delimiter,
				"Поиск по имени объекта $class"
			);
		} elseif ($this->attributeIsLink($attribute) || $this->attributeIsLoader($attribute)) {
			if ($link=$this->attributeIsLoader($attribute)) {
				// при указании loader поиск ведется по имени связанного объекта
				$schemaTemplate = [
					'type' => 'string',
				];
				$class=StringHelper::className($this->attributeLinkClass($link));
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					"Поиск по имени объекта $class"
				);
			} else {
				$class=StringHelper::className($this->attributeLinkClass($attribute));
				//иначе это сам атрибут ссылка (_id / _ids)
				if (StringHelper::endsWith($attribute, '_ids')) {
					$name .= ' (ссылки)';
					//для поиска по множественным ссылкам используем string с разделителями
					$schemaTemplate = [
						'type' => 'string',
						'example' => '1,2,3',
						'description' => "IDs объектов $class через запятую",
					];
				} elseif (StringHelper::endsWith($attribute, '_id')) {
					$name .= ' (ссылка)';
					$schemaTemplate = [
						'type' => 'integer',
						'example' => static::$intSample++,
					];
					$description=StringHelper::appendToDelimitedString(
						$description,$delimiter,
						"ID объекта $class"
					);
				}
			}
		} else {
			//База типа/формата для поиска — через apiSchema() класса-типа (строго, без fallback).
			$tc=$this->getAttributeTypeClass($attribute);
			$schemaTemplate=$tc->apiSchema();
			$type=$tc::name();	//тип для декораций ниже (заменяет getAttributeType)
			//Search-специфичные оверрайды/декорации (это search-механика, поэтому здесь, а не в типе):
			switch ($type) {
				case 'boolean':
				case 'toggle':
					$schemaTemplate=[
						'type' => 'string', // для поиска используем string, чтобы можно было передавать '0','1','true','false'
						'enum' => ['0','1','true','false'],
						'example' => '1'
					];
					if (!isset($data['fieldList']) && $type==='boolean')
						$description=StringHelper::appendToDelimitedString(
							$description,$delimiter,
							"0 - false, 1 - true"
						);
					break;
				case 'list':
				case 'radios':
					$schemaTemplate=[
						'type' => 'string', // для поиска используем string
						'example' => '1'
					];
					break;
				case 'date':
					$schemaTemplate['example']='2020-01-31';
					break;
				case 'datetime':
					$schemaTemplate['example']='2020-01-31 23:59:59';
					break;
				case 'ip':
				case 'ipNet':
				case 'ips':
					$schemaTemplate['example']='192.168.0.1/24';
					$description=StringHelper::appendToDelimitedString(
						$description,$delimiter,
						'IP адреса (опционально с маской подсети). По одному в строке.'
					);
					break;
				case 'macs':
					$schemaTemplate['example']='00:1A:2B:3C:4D:5E';
					$description=StringHelper::appendToDelimitedString(
						$description,$delimiter,
						'MAC адреса. По одному в строке.'
					);
					break;
				case 'urls':
					$schemaTemplate['example']='Ссылка на пример https://example.com/some/page';
					$description=StringHelper::appendToDelimitedString(
						$description,$delimiter,
						UrlListWidget::$APIhint
					);
					break;
			}
		}

		//если в метаданных есть пример, то используем его, а не тот что сгенерили по умолчанию
		if ($example=$this->getAttributeApiExample($attribute))
			$schemaTemplate['example']=$example;

		//если в метаданных есть список значений, то добавляем его в аннотацию
		if (isset($data['fieldList'])) {
			$fieldList=$data['fieldList'];
			if (is_callable($fieldList)) $fieldList=$fieldList();
			if (is_array($fieldList) && count($fieldList)>0) {
				$schemaTemplate['enum']=array_keys($fieldList);
				$schemaTemplate['example']=array_key_first($fieldList);
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					"Возможные значения: ",
					implode(', ',array_map(function($k,$v){return "$k - $v";},array_keys($fieldList),$fieldList))
				);
			}
		}

		//если строка, то смотрим на правила валидации на предмет ограничения длины
		if ($schemaTemplate['type']==='string') {
			foreach ($this->rules() as $rule) {
				if (
					in_array($attribute,(array)$rule[0])
					&&
					$rule[1] === 'string'
					&&
					isset($rule['max'])
				) {
					$schemaTemplate['maxLength']=$rule['max'];
				}
			}
		}

		if (isset($data['is_inheritable']) && $data['is_inheritable']) {
			$description=StringHelper::appendToDelimitedString(
				$description,$delimiter,
				'Атрибут наследуется от родительского объекта, если не задан явно.'
			);
		}

		if ($description) $name.=": $description";

		$parameterTemplate=[
			'name' => $attribute,
			'in' => 'query',
			'description' => $name,
			'schema' => $schemaTemplate,
			'_context' => $context,
		];

		return new \OpenApi\Annotations\Parameter($parameterTemplate);
	}

}