<?php

namespace app\types;

use app\generation\context\AttributeContext;
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
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		// Детерминированная генерация на основе seed + имя атрибута
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		// Имена пользователей
		$users = [
			'admin', 'support', 'info', 'sales', 'contact',
			'noreply', 'helpdesk', 'service', 'postmaster', 'webmaster',
		];

		// Домены
		$domains = [
			'example.com', 'example.org', 'example.net',
			'test.local', 'company.ru', 'org.net',
		];

		$userIndex = mt_rand(0, count($users) - 1);
		$domainIndex = mt_rand(0, count($domains) - 1);

		// Генерация случайного числа для уникальности
		$suffix = mt_rand(1, 99);

		$result = $users[$userIndex] . $suffix . '@' . $domains[$domainIndex];

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
