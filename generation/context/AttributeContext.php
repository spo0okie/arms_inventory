<?php

namespace app\generation\context;

use app\generation\context\GenerationContext;
use app\models\base\ArmsModel;

/**
 * Контекст генерации для конкретного атрибута
 */
class AttributeContext
{
    public function __construct(
        public readonly string $attribute,
        public readonly array $attributeData,
        public readonly ArmsModel $model,
        public readonly GenerationContext $generationContext,
    ) {}

    /**
     * Получить конфигурацию генератора из attributeData
     */
    public function generatorConfig(): array
    {
        return $this->attributeData['generator'] ?? [];
    }

    /**
     * Проверить, является ли атрибут nullable
     */
    public function isNullable(): bool
    {
        return $this->model->getAttributeIsNullable($this->attribute);
    }
}
