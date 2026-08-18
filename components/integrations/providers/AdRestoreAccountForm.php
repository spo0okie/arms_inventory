<?php

namespace app\components\integrations\providers;

/**
 * Форма действия восстановления уволенной учётки AD — ad/restore-account
 * (docs/dev/integrations.md, {@see AdUserProvider}).
 *
 * Восстановление разворачивает увольнение (usr_dismiss.ps1): учётка
 * включается, получает новый пароль (генерируется и уходит пользователю
 * по SMS — параметры пароля наследуются от формы сброса) и переезжает в
 * выбранное подразделение. Подразделение предзаполняется зеркалом пути
 * увольнения (скрипт увольнения переносит учётку в тот же подпуть под
 * корнем уволенных, {@see \app\components\ldap\LdapService::relocateDn()}).
 *
 * @property string $ou DN подразделения, куда вернуть учётку
 */
class AdRestoreAccountForm extends AdPasswordResetForm
{
	public $ou = '';

	public function rules()
	{
		return array_merge(parent::rules(), [
			['ou', 'required'],
			['ou', 'string', 'max' => 512],
		]);
	}

	public function attributeData()
	{
		return array_merge(parent::attributeData(), [
			'ou' => [
				'Подразделение',
				'hint' => 'Куда вернуть учётную запись. По умолчанию — зеркало пути, '
					.'по которому её увольняли (тот же подпуть в рабочем дереве).',
			],
		]);
	}
}
