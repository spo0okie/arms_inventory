<?php

namespace app\generation\generators;

use app\models\base\ArmsModel;

class GeneratorResolver
{
	public static function resolve(ArmsModel $model, array $attribute): string
	{
		$attributeData=$model->getAttributeData($attribute);
		
		$customClass=$attributeData['generator'] ?? null;
		if (!is_null($customClass)) return $customClass;

		$type=$model->getAttributeType($attribute,null);
		$class=get_class($model);
		if (is_null($type)) {
			throw new \Exception('Не удалось определить тип атрибута: '.$class.'->'.$attribute);
		}

		return self::getGeneratorClass($type);
	}

	public static function getGeneratorClass(string $type): string
	{
		switch ($type) {
			case 'boolean':		return BooleanGenerator::class;
			case 'date':		return DateGenerator::class;
			case 'datetime':	return DateTimeGenerator::class;
			case 'float':		return FloatGenerator::class;
			case 'integer':		return IntegerGenerator::class;
			case 'ips':			return IpsGenerator::class;
			case 'json':		return JsonGenerator::class;
			case 'macs':		return MacsGenerator::class;
			case 'string':		return StringGenerator::class;
			case 'string[]':	return StringArrayGenerator::class;
			case 'text':		return TextGenerator::class;
			case 'urls':		return UrlsGenerator::class;
			
			default:
				throw new \Exception('Не удалось определить генератор для типа: '.$type);
		}
	}

}