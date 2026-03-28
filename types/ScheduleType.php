<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\ScheduleGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения расписания рабочего/нерабочего времени.
 *
 * Формат: ЧЧ:ММ-ЧЧ:ММ или несколько интервалов через запятую
 * Пример: "09:00-12:00,13:00-18:00" или "-" (нерабочий день)
 */
class ScheduleType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'schedule';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? '09:00-18:00';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === '-' || $value === null) {
			return '<span class="text-muted">выходной</span>';
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
			'format' => 'schedule',
			'example' => '09:00-12:00,13:00-18:00',
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
			'09:00-18:00',
			'00:00-23:59',
			'09:00-12:00,13:00-18:00',
			'-',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		$generator = new ScheduleGenerator();
		return $generator->generate($context);
	}
}
