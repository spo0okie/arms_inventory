<?php

namespace app\generation\generators;

use app\generation\context\AttributeContext;
use app\helpers\StringHelper;

/**
 * Генератор для ссылочных атрибутов (link)
 */
class LinkGenerator implements GeneratorInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		$attr = $context->attribute;
		$isMany = StringHelper::endsWith($attr, '_ids');

		if ($context->empty) {
			if ($context->isNullable()) {
				return null;
			}
			return $isMany ? [] : 1;
		}

		return $isMany ? [1] : 1;
	}
}