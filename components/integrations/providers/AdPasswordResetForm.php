<?php

namespace app\components\integrations\providers;

use app\models\base\ArmsModel;
use app\models\base\traits\AttributeDataModelTrait;

/**
 * Форма действия ad-reset/reset-password (docs/dev/integrations.md).
 *
 * Цель — заменить поход в PowerShell на одну кнопку: пароль всегда
 * генерируется автоматически и НЕ показывается администратору (его
 * узнаёт только пользователь из SMS), поэтому ручного ввода пароля и
 * галочки «требовать смену» здесь нет. Настраиваются только: тип пароля
 * (произносимый/случайный), длина и разблокировка учётки.
 *
 * @property bool $pronounceable произносимый пароль (проще продиктовать)
 * @property int $length длина пароля (символов)
 * @property bool $unlock разблокировать учётку заодно
 */
class AdPasswordResetForm extends ArmsModel
{
	use AttributeDataModelTrait;

	/** минимум по парольной политике */
	const MIN_LENGTH = 12;
	const MAX_LENGTH = 64;

	public $pronounceable = true;
	public $length = self::MIN_LENGTH;
	public $unlock = false;

	public function rules()
	{
		return [
			[['pronounceable', 'unlock'], 'boolean'],
			['pronounceable', 'default', 'value' => true],
			['unlock', 'default', 'value' => false],
			['length', 'default', 'value' => self::MIN_LENGTH],
			['length', 'integer', 'min' => self::MIN_LENGTH, 'max' => self::MAX_LENGTH,
				'tooSmall' => 'Не короче '.self::MIN_LENGTH.' символов (парольная политика)',
				'tooBig' => 'Не длиннее '.self::MAX_LENGTH.' символов',
			],
		];
	}

	public function attributeData()
	{
		return [
			'length' => [
				'Длина пароля',
				'hint' => 'Число символов (минимум '.self::MIN_LENGTH.' по парольной политике). '
					.'Можно увеличить.',
			],
			'pronounceable' => [
				'Произносимый пароль',
				'hint' => 'Собирается из слогов - его проще продиктовать и ввести. '
					.'Снимите галочку для полностью случайного пароля (надёжнее, но труднее вводить).',
			],
			'unlock' => [
				'Разблокировать учётку',
				'hint' => 'Заодно снять блокировку учётной записи (если она заблокирована '
					.'из-за неудачных попыток входа).',
			],
		];
	}
}
