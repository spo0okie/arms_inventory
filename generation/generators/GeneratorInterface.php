<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Интерфейс генератора атрибутов
 */
interface GeneratorInterface
{
    /**
     * Сгенерировать значение атрибута
     *
     * @param AttributeContext $context Контекст генерации
     * @return mixed Сгенерированное значение
     */
    public function generate(AttributeContext $context): mixed;
}
