<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений серийного номера.
 *
 * Формат: буквенно-цифровой
 * Пример: "SN123456789", "ABC-123456", "2023X001"
 */
class SerialNumberGenerator implements GeneratorInterface
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

		// Префиксы для серийных номеров
		$prefixes = [
			'SN', 'S/N', 'PN', 'P/N', 'SN:', 'ABC', 'XYZ',
		];

		// Длина серийного номера
		$length = mt_rand(6, 12);
		$number = '';
		for ($i = 0; $i < $length; $i++) {
			// 50% вероятность цифры, 50% - буквы
			if (mt_rand(0, 1) === 0) {
				$number .= (string)mt_rand(0, 9);
			} else {
				$number .= chr(mt_rand(65, 90)); // A-Z
			}
		}

		$prefixIndex = mt_rand(0, count($prefixes) - 1);
		$result = $prefixes[$prefixIndex] . $number;

		mt_srand(); // сброс
		return $result;
	}
}
