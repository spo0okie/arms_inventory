<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Журнал действий интеграций с внешними ИС
 * (docs/dev/integrations.md).
 *
 * Каждое выполненное действие (L2/L2+) пишется сюда и при успехе, и при
 * ошибке. Вложенные вызовы (композиция провайдеров, §2.2 контракта)
 * ссылаются на запись-инициатор через parent_id. Секреты (пароли, тексты
 * с паролями) в params/message не попадают — фильтрация на стороне
 * провайдера (ActionResult::$logParams).
 */
class m260807_000001_create_integrations_log extends ArmsMigration
{
	public function up()
	{
		$this->createTable('integrations_log', [
			'id' => $this->primaryKey(),
			'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Когда выполнено'),
			'users_id' => $this->integer()->null()->comment('Инициатор (пользователь ARMS), NULL для консоли'),
			'provider' => $this->string(64)->notNull()->comment('Id провайдера интеграции'),
			'action' => $this->string(64)->notNull()->comment('Id действия'),
			'class' => $this->string(128)->null()->comment('Класс объекта ARMS (NULL для standalone)'),
			'object_id' => $this->integer()->null()->comment('Id объекта ARMS (NULL для standalone)'),
			'parent_id' => $this->integer()->null()->comment('Запись-инициатор для вложенных вызовов'),
			'ext_login' => $this->string(128)->null()->comment('Исполнитель во внешней ИС (L2+)'),
			'params' => $this->string(1024)->null()->comment('Параметры действия (JSON, без секретов)'),
			'result' => $this->string(8)->notNull()->comment('ok / error'),
			'message' => $this->string(255)->null()->comment('Итог/ответ внешней ИС'),
		]);

		$this->createIndex('idx-integrations_log-object', 'integrations_log', ['class', 'object_id']);
		$this->createIndex('idx-integrations_log-provider', 'integrations_log', ['provider', 'action']);

		$this->addForeignKey('fk-integrations_log-users_id', 'integrations_log', 'users_id', 'users', 'id', 'SET NULL');
		$this->addForeignKey('fk-integrations_log-parent_id', 'integrations_log', 'parent_id', 'integrations_log', 'id', 'SET NULL');
	}

	public function down()
	{
		$this->dropTableIfExists('integrations_log');
	}
}
