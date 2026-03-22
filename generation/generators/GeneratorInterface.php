<?php

namespace app\generation\generators;

interface GeneratorInterface
{
    /**
     * Генерация значения атрибута.
     *
     * Требования:
     * - не бросает исключения в нормальном сценарии
     * - не содержит бизнес-логики
     * - не обращается к БД
     */
    public static function generate(array $params): mixed;
}