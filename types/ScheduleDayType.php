<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\validators\DateValidator;
use yii\web\View;

/**
 * Тип для хранения дня недели или даты.
 *
 * Формат: ключ из словаря дней недели (1-7, def) ИЛИ дата в формате Y-m-d
 * Пример: "1" (понедельник), "def" (по умолчанию), "2024-03-15"
 */
class ScheduleDayType implements AttributeTypeInterface
{

	public static $dayNames = [
		'1' => 'Пн',
		'2' => 'Вт',
		'3' => 'Ср',
		'4' => 'Чт',
		'5' => 'Пт',
		'6' => 'Сб',
		'7' => 'Вс',
		'def' => 'по умолч.',
	];

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


		if (isset(static::$dayNames[$value])) {
			return Html::encode(static::$dayNames[$value]);
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
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		// Детерминированная генерация на основе seed + имя атрибута
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		// Варианты для генерации
		$options = array_keys(static::$dayNames);

		$index = mt_rand(0, count($options) - 1);
		$result = (string)$options[$index];

		mt_srand(); // сброс
		return $result;
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string',['max'=>'10']),
			new RuleDefinition(function($model,$attribute) {
				if (isset(static::$dayNames[$model->$attribute])) return; //день недели или default - OK
				$dateValidator=new DateValidator(['format'=>'php:Y-m-d']);
				if (!$dateValidator->validate($model->$attribute, $error)) {
					$model->addError($attribute, $error.': '.$model->$attribute);
					return; // stop on first error
				}
			}),
		];
	}

}
