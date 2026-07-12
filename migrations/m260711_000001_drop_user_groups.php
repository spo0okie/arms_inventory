<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Удаляет таблицы групп пользователей.
 *
 * Функциональность групп не используется: контроллер был удалён ранее
 * (8cb749d), REST-эндпоинта и пункта меню нет; по решению владельца
 * модель/вьюхи/таблицы вычищаются полностью (понадобятся — будут
 * созданы с нуля).
 */
class m260711_000001_drop_user_groups extends ArmsMigration
{
	public function up()
	{
		$this->dropTableIfExists('users_in_groups');
		$this->dropTableIfExists('user_groups');
	}

	public function down()
	{
		echo "m260711_000001_drop_user_groups cannot be reverted (tables dropped).\n";
		return false;
	}
}
