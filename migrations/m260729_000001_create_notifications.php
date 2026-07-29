<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Outbox-таблица механизма оповещений (plans/notifications.md, issue #184).
 *
 * Письма никогда не отправляются синхронно из веб-запроса: постановка в
 * очередь идёт в той же транзакции, что и породившее событие (откат
 * транзакции отменяет и уведомление), фактическую отправку делает
 * консольная команда notify/send.
 *
 * Отправленные записи (sent_at IS NOT NULL) хранятся: по ним notify/watch
 * определяет, когда можно слать повторное напоминание (repeat), чистка —
 * отложенная (notify/cleanup).
 */
class m260729_000001_create_notifications extends ArmsMigration
{
	public function up()
	{
		$this->createTable('notifications', [
			'id' => $this->primaryKey(),
			'user_id' => $this->integer()->notNull()->comment('Получатель'),
			'event_key' => $this->string(128)->null()->comment('Ключ дедупликации/повторов'),
			'subject' => $this->string(255)->notNull()->comment('Тема письма'),
			'body' => $this->getDb()->getSchema()->createColumnSchemaBuilder('mediumtext')->comment('Готовый HTML письма'),
			'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Поставлено в очередь'),
			'sent_at' => $this->timestamp()->null()->defaultValue(null)->comment('Отправлено (NULL = в очереди)'),
			'attempts' => $this->integer()->notNull()->defaultValue(0)->comment('Число неудачных попыток отправки'),
			'last_error' => $this->string(255)->null()->comment('Последняя ошибка отправки'),
		]);

		$this->createIndex('idx-notifications-sent_at', 'notifications', 'sent_at');
		$this->createIndex('idx-notifications-dedup', 'notifications', ['user_id', 'event_key', 'sent_at']);

		$this->addForeignKey('fk-notifications-user_id', 'notifications', 'user_id', 'users', 'id', 'CASCADE');
	}

	public function down()
	{
		$this->dropTableIfExists('notifications');
	}
}
