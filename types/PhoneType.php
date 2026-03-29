<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения телефонного номера.
 *
 * Формат: свободный, но типичные форматы:
 * Пример: "+7 (999) 123-45-67", "8-800-123-45-67", "123-45-67"
 */
class PhoneType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'phone';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['type'] = 'tel';
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? '+7 (999) 123-45-67';
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

		$encoded = Html::encode((string)$value);
		return Html::a($encoded, 'tel:' . preg_replace('/[^0-9+]/', '', $encoded), [
			'target' => '_blank',
			'rel' => 'noopener noreferrer',
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function apiSchema(): array
	{
		return [
			'type' => 'string',
			'format' => 'phone',
			'example' => '+7 (999) 123-45-67',
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
			'+7 (999) 123-45-67',
			'8-800-123-45-67',
			'+7 495 123-45-67',
			'123-45-67',
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

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
		];
	}
}
