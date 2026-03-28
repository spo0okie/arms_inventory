<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений инвентарного номера.
 *
 * Формат: токены через дефис <тип>-<серийный>
 * Пример: "T-001", "PC-12345", "MON-42"
 */
class InvNumGenerator implements GeneratorInterface
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

		// Префиксы для разных типов оборудования
		$prefixes = [
			'PC',     // Персональный компьютер
			'MON',    // Монитор
			'NET',    // Сетевое оборудование
			'SRV',    // Сервер
			'PRN',    // Принтер
			'PHN',    // Телефон
			'UPS',    // ИБП
			'SW',     // Коммутатор
			'RTR',    // Маршрутизатор
		];

		$prefixIndex = mt_rand(0, count($prefixes) - 1);
		$prefix = $prefixes[$prefixIndex];

		// Серийный номер (3-6 цифр)
		$serialLength = mt_rand(3, 6);
		$serial = '';
		for ($i = 0; $i < $serialLength; $i++) {
			$serial .= (string)mt_rand(0, 9);
		}

		$result = $prefix . '-' . $serial;

		mt_srand(); // сброс
		return $result;
	}
}
