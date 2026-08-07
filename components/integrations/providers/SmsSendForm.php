<?php

namespace app\components\integrations\providers;

use app\models\base\ArmsModel;
use app\models\base\traits\AttributeDataModelTrait;

/**
 * Форма параметров действия sms/send (перенос models/ui/SmsForm при
 * выносе SMS в механизм интеграций, plans/integrations-drafts.md §1).
 * Сама отправка — в {@see SmsProvider::runAction()}.
 *
 * @property string $phone номер куда отправлять
 * @property string $text что отправлять
 */
class SmsSendForm extends ArmsModel
{
	use AttributeDataModelTrait;

	public $phone;
	public $text;

	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			['phone', 'filter', 'filter' => function ($value) {
				//убираем пробелы по краям
				$value = trim($value ?? '');

				/* убираем посторонние символы из номера*/
				return preg_replace('/[^0-9]/', '', $value);
			}],
			['phone', 'string', 'min' => 11, 'max' => 11,
				'tooShort' => 'Номер должен быть ровно 11 знаков без пробелов',
				'tooLong' => 'Номер должен быть ровно 11 знаков без пробелов',
			],
			['text', 'string', 'max' => 128, 'tooShort' => 'Дофига длинно. Давай покороче'],
			[['phone', 'text'], 'required'],
		];
	}

	public function attributeData()
	{
		return [
			'phone' => 'Номер телефона',
			'text' => 'Текст сообщения',
		];
	}
}
