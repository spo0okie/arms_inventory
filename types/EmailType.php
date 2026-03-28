<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\EmailGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения email адреса.
 *
 * Формат: user@domain.example
 * Пример: "user@example.com"
 */
class EmailType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'email';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['type'] = 'email';
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? 'user@example.com';
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
		return Html::a($encoded, 'mailto:' . $encoded, [
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
			'format' => 'email',
			'example' => 'user@example.com',
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
			'admin@example.com',
			'support@example.org',
			'info@example.net',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		$generator = new EmailGenerator();
		return $generator->generate($context);
	}
}
