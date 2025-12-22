<?php

/**
 * Trait AttributeAnnotationModelTrait
 *
 * Методы для генерации аннотаций атрибутов модели для OpenAPI документации
 */

namespace app\models\traits;

use app\components\UrlListWidget;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use OpenApi\Annotations\Property;
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
		/** @var ArmsModel $this */
		$item=$this->getAttributeData($attribute);
		//если пример описан в метаданных, то возвращаем его
		if (isset($item['example']))
			return $item['example'];
		
		return $default;
	}
	
	
	/**
	 * Возвращает OpenApi\Annotations\Property для атрибута модели
	 * @param $attribute
	 * @param $context
	 * @return Property
	 * @throws UnknownPropertyException
	 */
	public function generateAttributeAnnotation($attribute,$context): Property
	{
		$delimiter="; ";
		/** @var ArmsModel $this */
		$data=$this->getAttributeData($attribute);
		$name=$this->getAttributeApiLabel($attribute);
		$hint=$this->getAttributeApiHint($attribute);
		$type=$this->getAttributeType($attribute);
		
		$description=$hint??'';
		if ($this->attributeIsExtra($attribute)) {
			$description=StringHelper::appendToDelimitedString(
				$description,$delimiter,
				"ДОПОЛНИТЕЛЬНОЕ ПОЛЕ: нужно запрашивать явно через параметр expand"
			);
		}
		
		
		$template=[];
		switch ($type) {
			case 'boolean':
			case 'toggle':
				$template=[
					'type' => 'boolean',
					'format' => 'integer',
					'enum' => [0,1],
					'example' => $this->getAttributeApiExample($attribute,1)
				];
				if (!isset($data['fieldList']) && $type==='boolean')
					$description=StringHelper::appendToDelimitedString(
						$description,$delimiter,
						"0 - false, 1 - true"
					);
				break;
			
			
			case 'list':
			case 'radios':
				$template=[
					'type' => 'integer',
					'format' => 'integer',
					'example' => static::$intSample++
				];
				break;
			
			case 'text':
			case 'ntext':
			case 'string':
				$template=[
					'type' => 'string',
				];
				break;
			
			case 'json_object':
				$template=[
					'type' => 'string',
					'format' => 'json object',
				];
				break;
			
				case 'json_array': $template=[
					'type' => 'string',
					'format' => 'json array',
				];
				break;
			
			case 'date':
				$template=[
					'type' => 'string',
					'format' => 'date',
					'example' => '2020-01-31',
				];
				break;
				
			case 'datetime':
				$template=[
					'type' => 'string',
					'format' => 'date-time',
					'example' => '2020-01-31 23:59:59',
				];
				break;
				
			case 'ips':
				$template=[
					'type' => 'string',
					'example' => '192.168.0.1/24',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					'IP адреса (опционально с маской подсети). По одному в строке.'
				);
				break;
				
			case 'macs':
				$template=[
					'type' => 'string',
					'example' => '00:1A:2B:3C:4D:5E',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					'MAC адреса. По одному в строке.'
				);
				break;
				
			case 'urls':
				$template=[
					'type' => 'string',
					'example' => 'Ссылка на пример https://example.com/some/page',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					UrlListWidget::$APIhint
				);
				break;
				
			case 'link':
				if ($link=$this->attributeIsLoader($attribute)) {
					
					$class=StringHelper::className($this->attributeLinkClass($link));
					//если это атрибут загрузчик, то это RO свойство содержащее другой объект
					$template = [
						'type' => 'object',
						'ref' => '#/components/schemas/' . $class,
						'readOnly' => true,
					];
				} else {
					$class=StringHelper::className($this->attributeLinkClass($attribute));
					//иначе это сам атрибут ссылка (_id / _ids)
					if (StringHelper::endsWith($attribute, '_ids')) {
						$name .= ' (ссылки)';
						//если множественная ссылка
						$template = [
							'type' => 'array',
							'items' => [
								'type' => 'integer',
								'example' => static::$intSample++,
								'description' => "IDs объектов $class",
							],
						];
					} elseif (StringHelper::endsWith($attribute, '_id')) {
						$name .= ' (ссылка)';
						$template = [
							'type' => 'integer',
							'example' => static::$intSample++,
						];
						$description=StringHelper::appendToDelimitedString(
							$description,$delimiter,
							"ID объекта $class"
						);
					}
				}
				break;
			default:
				$template=[
					'type' => $type,
				];
				break;
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
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					"Возможные значения:",
					implode(', ',array_map(function($k,$v){return "$k - $v";},array_keys($fieldList),$fieldList))
				);
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
			$description=StringHelper::appendToDelimitedString(
				$description,$delimiter,
				'Атрибут наследуется от родительского объекта, если не задан явно.'
			);
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
		/** @var ArmsModel $this */
		$data=$this->getAttributeData($attribute);
		$name=$this->getAttributeApiLabel($attribute);
		$hint=$this->getAttributeApiHint($attribute);
		$type=$this->getAttributeType($attribute);

		$description=$hint??'';
		$schemaTemplate=[];

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

			case 'text':
			case 'ntext':
			case 'string':
				$schemaTemplate=[
					'type' => 'string',
				];
				break;

			case 'json_object':
				$schemaTemplate=[
					'type' => 'string',
					'format' => 'json object',
				];
				break;

			case 'json_array':
				$schemaTemplate=[
					'type' => 'string',
					'format' => 'json array',
				];
				break;

			case 'date':
				$schemaTemplate=[
					'type' => 'string',
					'format' => 'date',
					'example' => '2020-01-31',
				];
				break;

			case 'datetime':
				$schemaTemplate=[
					'type' => 'string',
					'format' => 'date-time',
					'example' => '2020-01-31 23:59:59',
				];
				break;

			case 'ips':
				$schemaTemplate=[
					'type' => 'string',
					'example' => '192.168.0.1/24',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					'IP адреса (опционально с маской подсети). По одному в строке.'
				);
				break;

			case 'macs':
				$schemaTemplate=[
					'type' => 'string',
					'example' => '00:1A:2B:3C:4D:5E',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					'MAC адреса. По одному в строке.'
				);
				break;

			case 'urls':
				$schemaTemplate=[
					'type' => 'string',
					'example' => 'Ссылка на пример https://example.com/some/page',
				];
				$description=StringHelper::appendToDelimitedString(
					$description,$delimiter,
					UrlListWidget::$APIhint
				);
				break;

			case 'link':
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
				break;
			default:
				$schemaTemplate=[
					'type' => $type,
				];
				break;
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