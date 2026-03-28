<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\ScheduleDayGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения дня недели или даты.
 *
 * Формат: ключ из словаря дней недели (1-7, def) ИЛИ дата в формате Y-m-d
 * Пример: "1" (понедельник), "def" (по умолчанию), "2024-03-15"
 */
class ScheduleDayType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'schedule-day';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? '1-7, def, Y-m-d';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;

		if ($value === null) {
			return '<span class="text-muted">не задано</span>';
		}

		// День недели
		$dayNames = [
			'1' => 'Пн',
			'2' => 'Вт',
			'3' => 'Ср',
			'4' => 'Чт',
			'5' => 'Пт',
			'6' => 'Сб',
			'7' => 'Вс',
			'def' => 'по умолчанию',
		];

		if (isset($dayNames[$value])) {
			return Html::encode($dayNames[$value]);
		}

		// Дата в формате Y-m-d
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
			return Html::encode($value);
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
			'format' => 'schedule-day',
			'example' => '1',
			'description' => 'День недели (1-7) или дата (Y-m-d) или "def"',
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
			'1',      // Понедельник
			'2',      // Вторник
			'5',      // Пятница
			'def',    // По умолчанию
			'2024-03-15',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		$generator = new ScheduleDayGenerator();
		return $generator->generate($context);
	}
}
