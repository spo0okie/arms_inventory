<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений HEX цвета.
 *
 * Формат: #RRGGBB или #RGB
 * Пример: "#FF5733", "#fff", "#000000"
 */
class ColorGenerator implements GeneratorInterface
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

		// Предопределённые "хорошие" цвета (web-safe + популярные)
		$colors = [
			'#FF5733', // Оранжево-красный
			'#3498DB', // Синий
			'#2ECC71', // Зелёный
			'#E74C3C', // Красный
			'#9B59B6', // Фиолетовый
			'#F1C40F', // Жёлтый
			'#1ABC9C', // Бирюзовый
			'#34495E', // Тёмно-серый
			'#E67E22', // Оранжевый
			'#2980B9', // Тёмно-синий
			'#27AE60', // Тёмно-зелёный
			'#C0392B', // Тёмно-красный
			'#8E44AD', // Тёмно-фиолетовый
			'#D35400', // Тёмно-оранжевый
			'#16A085', // Тёмно-бирюзовый
			'#2C3E50', // Тёмно-синий серый
		];

		$index = mt_rand(0, count($colors) - 1);
		$result = $colors[$index];

		mt_srand(); // сброс
		return $result;
	}
}
