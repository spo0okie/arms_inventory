<?php

namespace app\generation\context;

use Random\Randomizer;
use Random\Engine\Mt19937;

/**
 * Контекст генерации на уровне всей модели
 */
class GenerationContext
{
	protected $rng=null;

    public function __construct(
        public readonly bool $empty = false,	//признак пустой модели
        public readonly int $seed = 0,			//детерменизм
		public readonly int $depth = 0,			//текущая глубина связей
    	public readonly int $maxDepth = 2,		//запрошенная глубина связей
    ) {}

	/**
	 * Формируем уникальный seed для генерации атрибута, учитывая модель, атрибут и глубину рекурсии
	 * @return int
	 */
	public function seed(): int
	{
		return $this->seed * ($this->depth + 1);
	}

	/**
	 * Получить изолированный генератор случайных чисел для этого атрибута.
	 * Каждый вызов возвращает новый Randomizer с детерминированным seed.
	 * 
	 * @return Randomizer Изолированный генератор случайных чисел
	 */
	public function randomizer(): Randomizer
	{		
		if ($this->rng === null) {
			$this->rng = new Randomizer(new Mt19937($this->seed));
		}
		return $this->rng;
	}
	
	/**
	 * Выбрать случайное значение из массива используя предоставленный RNG.
	 * Совместимо с PHP 8.2 (pickArrayValue доступен только с PHP 8.3).
	 * 
	 * @param array $array Массив для выбора
	 * @return mixed Случайное значение из массива
	 */
	public function pickRandomValue(array $array): mixed
	{
		if (empty($array)) {
			throw new \InvalidArgumentException('Cannot pick value from empty array');
		}
		
		$index = $this->randomizer()->getInt(0, count($array) - 1);
		return $array[$index];
	}
	
}
