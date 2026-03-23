<?php

namespace app\generation\context;

/**
 * Контекст генерации на уровне всей модели
 */
class GenerationContext
{
    public function __construct(
        public readonly bool $empty = false,	//признак пустой модели
        public readonly int $seed = 0,			//детерменизм
		public readonly int $depth = 0,			//текущая глубина связей
    	public readonly int $maxDepth = 2,		//запрошенная глубина связей
    ) {}
}
