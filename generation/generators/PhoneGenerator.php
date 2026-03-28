<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений телефонного номера.
 *
 * Формат: +7 (XXX) XXX-XX-XX, 8-XXX-XXX-XX-XX, и т.д.
 * Пример: "+7 (999) 123-45-67"
 */
class PhoneGenerator implements GeneratorInterface
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

		// Форматы телефонных номеров
		$formats = [
			'+7 (XXX) XXX-XX-XX',      // Страна + код + номер
			'8-XXX-XXX-XX-XX',          // Российский без +
			'+7XXX-XXX-XX-XX',          // Слитно
			'8 (XXX) XXX-XX-XX',        // Альтернативный
			'XXX-XX-XX',                // Внутренний номер
		];

		$formatIndex = mt_rand(0, count($formats) - 1);
		$format = $formats[$formatIndex];

		// Генерация случайных цифр для заполнения
		$result = preg_replace_callback('/X+/', function ($matches) {
			$length = strlen($matches[0]);
			$number = '';
			for ($i = 0; $i < $length; $i++) {
				$number .= (string)mt_rand(0, 9);
			}
			return $number;
		}, $format);

		mt_srand(); // сброс
		return $result;
	}
}
