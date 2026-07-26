<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Журнал изменений сотрудников (users_history) + стандартные поля авторства
 * updated_at/updated_by в самой таблице users.
 *
 * Мотивация: сотрудники массово правятся синхронизацией с кадровой БД
 * (SAPsync -> REST PUT), и без журнала не видно, что именно и когда она
 * поменяла. Журнал стандартный для ARMS: пара {Model}History (HistoryModel),
 * запись создаётся в afterSave при реальных изменениях, автор берётся из
 * updated_by (REST-синхронизация пишет логин сервисного пользователя).
 *
 * Зеркалируются только бизнес-поля: секреты (password, auth_key,
 * access_token) в журнал не попадают принципиально.
 *
 * FK на уровне БД не создаём (связи — через linksSchema модели),
 * индексы под выборки журнала присутствуют.
 */
class M260726110000CreateTableUsersHistory extends ArmsMigration
{
	/**
	 * Есть ли колонка в таблице (свежая схема, без кэша)
	 */
	private function columnExists(string $table, string $column): bool
	{
		$schema = $this->db->getTableSchema($table, true);
		return $schema !== null && $schema->getColumn($column) !== null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		//стандартные поля авторства в мастер-таблице (их подхватывает ArmsModel::beforeSave)
		if (!$this->columnExists('users', 'updated_at')) {
			$this->addColumn('users', 'updated_at', $this->timestamp()->null()->comment('Дата последнего изменения'));
		}
		if (!$this->columnExists('users', 'updated_by')) {
			$this->addColumn('users', 'updated_by', $this->string(32)->null()->comment('Автор последних изменений (username)'));
		}

		if (!$this->tableExists('users_history')) {
			$this->createTable('users_history', [
				'id' => $this->primaryKey(),
				'master_id' => $this->integer(),
				'employee_id' => $this->string(16),
				'org_id' => $this->integer(),
				'Orgeh' => $this->string(16),
				'Doljnost' => $this->string(255),
				'Ename' => $this->string(255),
				'Persg' => $this->integer(),
				'Uvolen' => $this->boolean(),
				'Login' => $this->string(32),
				'Email' => $this->string(64),
				'Phone' => $this->string(32),
				'Mobile' => $this->string(255),
				'work_phone' => $this->string(32),
				'Bday' => $this->string(16),
				'manager_id' => $this->integer(),
				'employ_date' => $this->string(16),
				'resign_date' => $this->string(16),
				'nosync' => $this->boolean(),
				'notepad' => $this->text(),
				'private_phone' => $this->string(255),
				'external_links' => $this->text(),
				'uid' => $this->string(64),
				'ips' => $this->string(255),
				'updated_at' => $this->timestamp()->null(),
				'updated_by' => $this->string(32),
				'updated_comment' => $this->string(),
				'changed_attributes' => $this->text(),
			]);

			$this->createIndex('idx-users_history-master_id', 'users_history', 'master_id');
			$this->createIndex('idx-users_history-updated_at', 'users_history', 'updated_at');
			$this->createIndex('idx-users_history-updated_by', 'users_history', 'updated_by');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('users_history');
		if ($this->columnExists('users', 'updated_by')) {
			$this->dropColumn('users', 'updated_by');
		}
		if ($this->columnExists('users', 'updated_at')) {
			$this->dropColumn('users', 'updated_at');
		}
	}
}
