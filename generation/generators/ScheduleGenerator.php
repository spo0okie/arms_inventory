<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений расписания.
 *
 * Формат: ЧЧ:ММ-ЧЧ:ММ или несколько интервалов через запятую
 * Пример: "09:00-12:00,13:00-18:00" или "-" (нерабочий день)
 */
class ScheduleGenerator implements GeneratorInterface
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

		// Варианты расписания для генерации
		$schedules = [
			'09:00-18:00',           // Стандартный рабочий день
			'00:00-23:59',           // Круглосуточно
			'09:00-12:00,13:00-18:00', // С перерывом на обед
			'10:00-19:00',           // Поздняя смена
			'08:00-17:00',           // Ранняя смена
			'09:00-17:00',           // Короткий день
			'09:00-13:00',           // Утро
			'14:00-18:00',           // Вечер
		];

		$index = mt_rand(0, count($schedules) - 1);
		$result = $schedules[$index];

		mt_srand(); // сброс
		return $result;
	}
}
