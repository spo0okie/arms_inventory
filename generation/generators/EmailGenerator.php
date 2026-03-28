<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений email адреса.
 *
 * Формат: user@domain.example
 * Пример: "user@example.com"
 */
class EmailGenerator implements GeneratorInterface
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
}
