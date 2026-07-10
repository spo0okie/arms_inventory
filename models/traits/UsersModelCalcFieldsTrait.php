<?php
/**
 * Вычисляемые поля для Сотрудников/пользователей
 */

namespace app\models\traits;

use app\models\Users;

trait UsersModelCalcFieldsTrait
{
	/**
	 * Возвращает эффективный номер телефона пользователя:
	 *  - сначала ищет VoIP-номера привязанного оборудования (Techs.voipPhones
	 *    и сами телефоны-аппараты с isVoipPhone)
	 *  - если таких нет, возвращает явно указанный внутренний номер (Phone)
	 * @return string
	 */
	public function getEffectivePhone()
	{
		/** @var Users $this */
		$numbers=[];

		foreach ($this->techs as $tech) {
			foreach ($tech->voipPhones as $phone) {
				if (strlen($phone->comment) && (int)$phone->comment)
					$numbers[(int)$phone->comment]=(int)$phone->comment;
			}
			if ($tech->isVoipPhone && strlen($tech->comment) && (int)$tech->comment) {
				$numbers[(int)$tech->comment]=(int)$tech->comment;
			}
		}

		if (count($numbers)) return implode(', ',$numbers);

		return $this->Phone;
	}
}
