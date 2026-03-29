<?php

namespace app\types;

use app\models\base\ArmsModel;

/**
 * Контекст для генерации правила валидации атрибута для конкретной модели
 */
class AttributeRuleContext
{
    public function __construct(
        public ArmsModel $model,		
        public string $attribute
    ) {}
}