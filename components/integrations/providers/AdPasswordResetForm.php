<?php

namespace app\components\integrations\providers;

use app\models\base\ArmsModel;
use app\models\base\traits\AttributeDataModelTrait;

/**
 * Форма параметров действия ad-reset/reset-password
 * (docs/dev/integrations.md)
 *
 * @property string $password новый пароль (пусто = сгенерировать)
 * @property bool $mustChange потребовать смену пароля при входе
 */
class AdPasswordResetForm extends ArmsModel
{
	use AttributeDataModelTrait;

	public $password;
	public $mustChange = true;

	public function rules()
	{
		return [
			['password', 'trim'],
			['password', 'string', 'min' => 8, 'max' => 64,
				'tooShort' => 'Не короче 8 символов (или оставьте пустым для генерации)'],
			['mustChange', 'boolean'],
			['mustChange', 'default', 'value' => true],
		];
	}

	public function attributeData()
	{
		return [
			'password' => [
				'Новый пароль',
				'hint' => 'Оставьте пустым - пароль будет сгенерирован автоматически. '
					.'В журнал пароль не попадает в любом случае.',
			],
			'mustChange' => [
				'Сменить пароль при входе',
				'hint' => 'Потребовать у пользователя смену пароля при первом входе',
			],
		];
	}
}
