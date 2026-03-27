<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;

/**
 * Резолвер генераторов - определяет какой генератор использовать для атрибута
 */
class GeneratorResolver
{

	protected static $typeGenerators=[];

    /**
     * Получить экземпляр генератора для атрибута
     *
     * @param AttributeContext $context Контекст атрибута
     * @return GeneratorInterface Экземпляр генератора
     * @throws \Exception
     */
    public static function resolve(AttributeContext $context): GeneratorInterface
    {
        $attributeData = $context->attributeData;
        
        // 1. Если указан кастомный класс генератора в конфиге
        $customClass = $attributeData['generator']['class'] ?? null;
        if ($customClass !== null) {
            return new $customClass();
        }

		// 2. Если указан класс типа атрибута
		$typeInstance = $context->model->getAttributeTypeForGeneration($context->attribute);
		if ($typeInstance) {
			return $typeInstance;
		}

        // 3. Иначе определяем по типу атрибута
        $type = $context->model->getAttributeType($context->attribute, null);
		if (!is_string($type)) 
			throw new \Exception('Не удалось определить типа ' . get_class($context->model).'->'.$context->attribute);

		if (isset(self::$typeGenerators[$type])) return self::$typeGenerators[$type];
        return self::$typeGenerators[$type]=self::createGenerator($type);
    }

	/**
	 * Получить класс генератора по типу атрибута.
	 * Используется в тестах покрытия типов.
	 *
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	public static function getGeneratorClass(string $type): string
	{
		return get_class(self::createGenerator($type));
	}

    /**
     * Создать генератор по типу атрибута
     *
     * @param string $type Тип атрибута
     * @return GeneratorInterface
     * @throws \Exception
     */
    public static function createGenerator(string $type): GeneratorInterface
    {
        return match ($type) {
            'boolean'   => new BooleanGenerator(),
            'date'      => new DateGenerator(),
            'datetime'  => new DateTimeGenerator(),
            'float'     => new FloatGenerator(),
            'number'    => new FloatGenerator(),
            'integer'   => new IntegerGenerator(),
            'ips'       => new IpsGenerator(),
            'json'      => new JsonGenerator(),
            'macs'      => new MacsGenerator(),
            'link'      => new LinkGenerator(),
            'string'    => new StringGenerator(),
            'string[]'  => new StringArrayGenerator(),
            'text'      => new TextGenerator(),
            'urls'      => new UrlsGenerator(),
            default     => throw new \Exception('Не удалось определить генератор для типа: ' . $type),
        };
    }
}
