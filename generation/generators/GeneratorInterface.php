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
	 * $params = [
     * 		// режим генерации (пустое ли значение нужно и выглядит ли пустое как null)
     *     'empty' => bool,
     *     'nullable' => bool,
     * 
     * 		// типовые ограничения (уже извлечены снаружи)
     *     'min' => int|float|null,
     *     'max' => int|float|null,
     * 
     * 		// детерминизм
     *     'seed' => int|null,
     * ];
     */
    public static function generate(array $params): mixed;
}