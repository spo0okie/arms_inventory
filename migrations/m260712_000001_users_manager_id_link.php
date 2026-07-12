<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Превращает users.manager_id из строкового идентификатора кадровой
 * системы (string 16, никогда не заполнялся — поле не было доделано)
 * в настоящую ссылку на запись сотрудника (int).
 *
 * Старые значения обнуляются: они несли кадровый идентификатор, а не id
 * записи этой базы, и в существующих инсталляциях не проставлялись.
 */
class m260712_000001_users_manager_id_link extends ArmsMigration
{
	public function up()
	{
		$this->update('users', ['manager_id' => null]);
		$this->alterColumn('users', 'manager_id', $this->integer()->null());
	}

	public function down()
	{
		$this->update('users', ['manager_id' => null]);
		$this->alterColumn('users', 'manager_id', $this->string(16)->null());
	}
}
