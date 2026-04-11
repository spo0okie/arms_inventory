<?php

namespace app\models\base\traits;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\generation\ModelFactory;
use app\models\base\ArmsModel;

/**
 * ValidationGenerationTrait
 *
 * Трейт для обработки правил валидации и логики генерации в ArmsModel.
 * Содержит методы для применения бизнес-правил при генерации модели.
 */
trait ValidationGenerationTrait
{
	
	/**
	 * Вызывается после генерации атрибутов в ModelFactory.
	 * Применяет бизнес-правила, чтобы привести модель в валидное состояние до этапа валидации.
	 *
	 * @param GenerationContext $context Контекст генерации
	 * @param array             $options Generation options
	 * @return void
	 */
    public function afterGenerate(GenerationContext $context, array $options = []): void
    {
        $this->applyRequireOneOfRules($context, $options);
    }

    /**
     * Валидация того, что как минимум один из набора атрибутов не пустой.
     * @param string $attribute Attribute name
     * @param array $params Parameters with 'attrs' key containing attribute list
     * @return bool
     */
    public function validateRequireOneOf(string $attribute, array $params = []): bool
	{
        foreach ($params['attrs'] ?? [] as $attr) {
            // If at least one attribute is not empty, validation passes
            if (!static::attrIsEmpty($this, $attr)) {
                return true;
            }
        }
        
        // Add error to all attributes in the set
        foreach ($params['attrs'] ?? [] as $attr) {
            $this->addError($attr, $params['message'] ?? 'Как минимум один аттрибут должен быть заполнен');
        }
        
        return false;
    }

    /**
     * Apply validateRequireOneOf rules during generation.
     * Fills the first suitable attribute if none are filled.
     *
     * @param array $options Generation options
     */
    protected function applyRequireOneOfRules(GenerationContext $context, array $options = []): void
    {
        foreach ($this->rules() as $rule) {
            if (($rule[1] ?? null) !== 'validateRequireOneOf') {
                continue;
            }
            
            $attrs = $rule['params']['attrs'] ?? null;
            if (!is_array($attrs) || empty($attrs)) {
                continue;
            }
			
            foreach ($attrs as $attr) {
				//если атрибут не пустой, то вот и славно
				if (!static::attrIsEmpty($this, $attr)) continue;
			}
			
			// Выбираем случайный атрибут из набора для заполнения, остальные оставляем пустыми
			$attr=$context->pickRandomValue($attrs);
			
			$attrContext=new AttributeContext(
				attribute: $attr,
				empty: false,	//нужный атрибут заполняем
				model: $this,
				generationContext: $context
			);
			ModelFactory::generateAttribute($attrContext);
        }
    }


		
	/**
	 * Проверяет, что аттрибут или integer или их массив
	 * @param $attribute
	 */
	public function validateIntegerOrArrayOfInteger($attribute)
	{
		if (!is_int($this->$attribute) &&
			!(is_array($this->$attribute) &&
				count(array_filter($this->$attribute, 'is_int')) == count($this->$attribute))) {
			$this->addError($attribute, 'ID должен быть целым числом или массивом целых чисел');
		}
	}
}


