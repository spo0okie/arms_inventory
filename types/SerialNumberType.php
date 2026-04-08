<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

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
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? 'SN123456789';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === null || $value === '') {
			return '<span class="text-muted">—</span>';
		}
		return Html::encode((string)$value);
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
