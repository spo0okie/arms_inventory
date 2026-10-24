<?php

namespace app\types;

use app\generation\context\AttributeContext;
use yii\base\Model;

/** Номер VLAN IEEE 802.1Q; при создании допускается включительный диапазон. */
class VlanType extends IntegerType
{
	public const MIN_ID = 1;
	public const MAX_ID = 4094;

	/** {@inheritdoc} */
	public static function name(): string
	{
		return 'vlan';
	}

	/** {@inheritdoc} */
	public function inputHint(): ?string
	{
		return 'Номер VLAN от 1 до 4094. Значения 0 и 4095 имеют специальное назначение и недоступны для создания VLAN.<br>'
			.'Номера 1002-1005 разрешены в инвентаризации, но могут быть зарезервированы оборудованием (например, Cisco).<br>'
			.'Диапазон через тире (<b>10-20</b>) можно указать только при создании.';
	}

	/**
	 * Разбирает номер или диапазон без преобразования ошибочного ввода в число.
	 * @param mixed $value
	 * @return int[]|null Начало и конец включительно; null при ошибке.
	 */
	public static function bounds($value): ?array
	{
		if ((!is_string($value) && !is_int($value))
			|| !preg_match('/^([0-9]+)(?:\s*-\s*([0-9]+))?$/D', trim((string)$value), $matches)) return null;
		$start = (int)$matches[1];
		$end = isset($matches[2]) ? (int)$matches[2] : $start;
		return $start >= self::MIN_ID && $end <= self::MAX_ID && $start <= $end ? [$start, $end] : null;
	}

	/** Проверяет формат и единые границы VLAN для модели и правил типа. */
	public static function validateValue(Model $model, string $attribute, bool $allowRange): void
	{
		$value = $model->$attribute;
		if (self::bounds($value) === null) {
			$model->addError($attribute, 'Укажите целый номер VLAN от 1 до 4094 или корректный диапазон этих номеров.');
		} elseif (!$allowRange && str_contains((string)$value, '-')) {
			$model->addError($attribute, 'Диапазон VLAN можно задавать только при создании.');
		}
	}

	/** {@inheritdoc} */
	public function generate(AttributeContext $context): mixed
	{
		if ($context->empty) return $context->isNullable() ? null : self::MIN_ID;
		return $context->randomizer()->getInt(
			max(self::MIN_ID, $context->min ?? self::MIN_ID),
			min(self::MAX_ID, $context->max ?? self::MAX_ID)
		);
	}

	/** {@inheritdoc} */
	public function rules(AttributeRuleContext $context): array
	{
		// InlineValidator перепривязывает closure к модели: контекст передаём явно.
		return [new RuleDefinition(\yii\validators\InlineValidator::class, [
			'skipOnEmpty' => false,
			'method' => function ($attribute) use ($context) {
				VlanType::validateValue($context->model, $attribute, $context->model->isNewRecord);
			},
		])];
	}
}
