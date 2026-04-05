<?php

namespace app\generation\context;

use app\generation\context\GenerationContext;
use app\models\base\ArmsModel;

/**
 * Контекст генерации для конкретного атрибута
 */
class AttributeContext
{
	public $min=null;						//min значение/длина
	public $max=null;						//max значение/длина

	public function __construct(
        public readonly string $attribute,		//атрибут
		public readonly bool $empty=false,		//нужно ли пустой
        public readonly ArmsModel $model,
        public readonly GenerationContext $generationContext,
    ) {}

    /**
     * Получить конфигурацию генератора из attributeData
     */
    public function generatorConfig(): array
    {
        $attributeData = $this->model->getAttributeData($this->attribute);
        return is_array($attributeData) ? ($attributeData['generator'] ?? []) : [];
    }

    /**
     * Проверить, является ли атрибут nullable
     */
    public function isNullable(): bool
    {
        return $this->model->getAttributeIsNullable($this->attribute);
    }
}






