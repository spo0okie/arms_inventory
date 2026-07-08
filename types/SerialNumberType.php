<?php

namespace app\types;

use app\generation\context\AttributeContext;

/**
 * Тип для хранения серийного номера оборудования.
 *
 * Формат: свободный, но обычно буквенно-цифровой
 * Пример: "SN123456789", "ABC-123456", "2023X001"
 */
class SerialNumberType extends StringType
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'serial-number';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}

	/**
	 * {@inheritdoc}
	 */
	public function apiSchema(): array
	{
		return [
			'type' => 'string',
			'format' => 'serial-number',
			'example' => 'SN123456789',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function gridColumnClass(): ?string
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function samples(): array
	{
		return [
			'SN123456789',
			'ABC-123456',
			'2023X001',
			'S/N: ABC123DEF',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		// Префиксы для серийных номеров
		$prefixes = [
			'SN', 'S/N', 'PN', 'P/N', 'SN:', 'ABC', 'XYZ',
		];

		// Длина серийного номера
		$length = $rng->getInt(6, 12);
		$number = '';
		for ($i = 0; $i < $length; $i++) {
			// 50% вероятность цифры, 50% - буквы
			if ($rng->getInt(0, 1) === 0) {
				$number .= (string)$rng->getInt(0, 9);
			} else {
				$number .= chr($rng->getInt(65, 90)); // A-Z
			}
		}

		$prefix = AttributeContext::pickRandomValue($prefixes, $rng);
		return $prefix . $number;
	}
}
