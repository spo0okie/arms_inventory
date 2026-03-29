<?php

namespace app\types;

use app\models\base\ArmsModel;

/**
 * Класс определения правила внутри типа
 * нужен чтобы потом для конкретной модели для конкретного имени атрибута
 * сгенерировать правило для валидации атрибута 
 * (только на соответствие типу, бизнес логика валидации должна быть в модели)
 */
class RuleDefinition
{
    public function __construct(
        public string|\Closure $validator,
        public array $params = [],
    ) {}


    public function toYiiRule(string $attribute, ArmsModel $model): array
    {
        if ($this->validator instanceof \Closure) {
            // оборачиваем так, чтобы closure получил модель
            $wrapped = function ($attr) use ($model) {
                ($this->validator)($model, $attr);
            };

            return [[$attribute], $wrapped];
        }

        return array_merge([[$attribute], $this->validator], $this->params);
    }
}