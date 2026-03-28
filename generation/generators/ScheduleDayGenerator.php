<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений дня недели/даты.
 *
 * Формат: ключ из словаря дней недели (1-7, def) ИЛИ дата в формате Y-m-d
 * Пример: "1" (понедельник), "def" (по умолчанию), "2024-03-15"
 */
class ScheduleDayGenerator implements GeneratorInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		// Детерминированная генерация на основе seed + имя атрибута
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		// Варианты для генерации
		$options = [
			'1',      // Понедельник
			'2',      // Вторник
			'3',      // Среда
			'4',      // Четверг
			'5',      // Пятница
			'6',      // Суббота
			'7',      // Воскресенье
			'def',    // По умолчанию
		];

		$index = mt_rand(0, count($options) - 1);
		$result = $options[$index];

		mt_srand(); // сброс
		return $result;
	}
}
