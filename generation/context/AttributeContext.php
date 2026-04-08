<?php

namespace app\generation\context;

use app\generation\context\GenerationContext;
use app\models\base\ArmsModel;
use Random\Randomizer;
use Random\Engine\Mt19937;

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
	
	/**
	 * Получить изолированный генератор случайных чисел для этого атрибута.
	 * Каждый вызов возвращает новый Randomizer с детерминированным seed.
	 * 
	 * @return Randomizer Изолированный генератор случайных чисел
	 */
	public function randomizer(): Randomizer
	{
		$seed = (
			$this->generationContext->seed
			+ crc32(get_class($this->model))
			+ crc32($this->attribute)
		) * ($this->generationContext->depth + 1);
		
		return new Randomizer(new Mt19937($seed));
	}
	
	/**
	 * Выбрать случайное значение из массива используя предоставленный RNG.
	 * Совместимо с PHP 8.2 (pickArrayValue доступен только с PHP 8.3).
	 * 
	 * @param array $array Массив для выбора
	 * @param Randomizer $rng Генератор случайных чисел
	 * @return mixed Случайное значение из массива
	 */
	public static function pickRandomValue(array $array, Randomizer $rng): mixed
	{
		if (empty($array)) {
			throw new \InvalidArgumentException('Cannot pick value from empty array');
		}
		
		$index = $rng->getInt(0, count($array) - 1);
		return $array[$index];
	}
	
	/**
	 * @deprecated Использовать randomizer() вместо seed()
	 * Формируем уникальный seed для генерации атрибута, учитывая модель, атрибут и глубину рекурсии
	 * @return int
	 */
	public function seed(): int
	{
		return (
			$this->generationContext->seed
			+ crc32(get_class($this->model))
			+ crc32($this->attribute)
		) * ($this->generationContext->depth + 1);
	}
}






