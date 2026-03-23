<?php

namespace app\generation;

/**
 * Контекст генерации на уровне всей модели
 */
class GenerationContext
{
    public function __construct(
        public readonly bool $empty = false,
        public readonly int $seed = 0,
    ) {}
}
