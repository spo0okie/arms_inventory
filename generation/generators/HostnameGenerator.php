<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;

/**
 * Генератор значений hostname.
 *
 * Формат: hostname или hostname.domain.example
 * Пример: "server01", "dc01.domain.local", "pc-001"
 */
class HostnameGenerator implements GeneratorInterface
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

		// Префиксы для имён хостов
		$prefixes = [
			'server', 'srv', 'host', 'pc', 'ws',
			'dc', 'dc01', 'filesrv', 'mail', 'web',
			'db', 'db01', 'app', 'app01', 'vpn',
		];

		// Домены
		$domains = [
			'domain.local',
			'company.local',
			'corp.example.com',
			'office.local',
			'data.local',
		];

		$prefixIndex = mt_rand(0, count($prefixes) - 1);
		$domainIndex = mt_rand(0, count($domains) - 1);

		// Номер (1-99)
		$number = mt_rand(1, 99);

		// NetBIOS имя (без домена) или FQDN (с доменом)
		if (mt_rand(0, 1) === 0) {
			$result = $prefixes[$prefixIndex] . sprintf('%02d', $number);
		} else {
			$result = $prefixes[$prefixIndex] . sprintf('%02d', $number) . '.' . $domains[$domainIndex];
		}

		mt_srand(); // сброс
		return $result;
	}
}
