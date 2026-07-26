<?php

namespace app\models;

/**
 * Журнал изменений сотрудников (зеркало таблицы users_history).
 *
 * Пишется штатным механизмом HistoryModel из Users::afterSave (historyCommit):
 * запись создаётся только при реальных изменениях бизнес-полей. Автор изменений
 * (updated_by) заполняется в ArmsModel::beforeSave из логина текущего
 * пользователя — для REST-синхронизации (SAPsync) это логин сервисного
 * пользователя, так что видно, какие изменения внесены синхронизацией.
 *
 * Секреты (password, auth_key, access_token) не журналируются —
 * их колонок нет в users_history.
 *
 * UsersModelCalcFieldsTrait сюда сознательно НЕ прикрепляется: его
 * единственное calc-поле effectivePhone считается по связи techs,
 * которой у журнальной записи нет.
 *
 * @property int $id
 * @property int|null $master_id
 * @property string|null $employee_id
 * @property int|null $org_id
 * @property string|null $Orgeh
 * @property string|null $Doljnost
 * @property string|null $Ename
 * @property int|null $Persg
 * @property int|null $Uvolen
 * @property string|null $Login
 * @property string|null $Email
 * @property string|null $Phone
 * @property string|null $Mobile
 * @property string|null $work_phone
 * @property string|null $Bday
 * @property int|null $manager_id
 * @property string|null $employ_date
 * @property string|null $resign_date
 * @property int|null $nosync
 * @property string|null $notepad
 * @property string|null $private_phone
 * @property string|null $external_links
 * @property string|null $uid
 * @property string|null $ips
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 */
class UsersHistory extends HistoryModel
{
	public static $title = 'Изменение сотрудника';
	public static $titles = 'Изменения сотрудников';

	public $masterClass = Users::class;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'users_history';
	}
}
