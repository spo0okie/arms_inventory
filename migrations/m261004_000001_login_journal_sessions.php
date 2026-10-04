<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Журнал входов: сессии с окончанием.
 *
 * До сих пор журнал хранил только событие входа. Служба инвентаризации
 * Windows (arms.winservice) сообщает и окончание сессии, поэтому запись
 * получает ключ сессии, время и тип окончания и флаги достоверности времени.
 *
 * Записи без session_uid (старые скрипты и вся прежняя история) остаются
 * как есть: открытой считается только сессия с session_uid и без end_time,
 * так что миграция данных не нужна.
 */
class m261004_000001_login_journal_sessions extends ArmsMigration
{
	public function up()
	{
		$this->addColumnIfNotExists('login_journal', 'session_uid',
			$this->string(36)->null()->comment('Ключ сессии (GUID службы инвентаризации)'));
		$this->addColumnIfNotExists('login_journal', 'end_time',
			$this->dateTime()->null()->comment('Время окончания сессии (скорректированное)'));
		$this->addColumnIfNotExists('login_journal', 'end_type',
			$this->smallInteger()->null()->comment('Тип окончания сессии'));
		$this->addColumnIfNotExists('login_journal', 'flags',
			$this->integer()->notNull()->defaultValue(0)->comment('Флаги достоверности времени (битовая маска)'));
		$this->addColumnIfNotExists('login_journal', 'closed_by',
			$this->string(128)->null()->comment('Кто закрыл сессию вручную'));

		if (!$this->indexExists('login_journal_session_uid_idx', 'login_journal')) {
			$this->createIndex('login_journal_session_uid_idx', 'login_journal', 'session_uid', true);
		}
		//выборки открытых сессий: по ОС и по сотруднику
		if (!$this->indexExists('login_journal_comp_open_idx', 'login_journal')) {
			$this->createIndex('login_journal_comp_open_idx', 'login_journal', ['comps_id', 'end_time']);
		}
		if (!$this->indexExists('login_journal_user_open_idx', 'login_journal')) {
			$this->createIndex('login_journal_user_open_idx', 'login_journal', ['users_id', 'end_time']);
		}
	}

	public function down()
	{
		foreach (['login_journal_session_uid_idx', 'login_journal_comp_open_idx', 'login_journal_user_open_idx'] as $index) {
			if ($this->indexExists($index, 'login_journal')) {
				$this->dropIndex($index, 'login_journal');
			}
		}
		foreach (['session_uid', 'end_time', 'end_type', 'flags', 'closed_by'] as $column) {
			$this->dropColumnIfExists('login_journal', $column);
		}
	}
}
