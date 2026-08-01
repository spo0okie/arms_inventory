<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Разрешает NULL в comps_rescan_queue.soft_id: пустое значение означает
 * полный рескан ОС (изменился отпечаток софта/паспорт самой ОС),
 * а не рескан из-за конкретного продукта.
 */
class M260731100000CompsRescanQueueSoftIdNullable extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->alterColumn('comps_rescan_queue', 'soft_id', $this->integer()->null());
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		//возвращаем NOT NULL; висящие задания полного рескана при откате удаляем
		$this->delete('comps_rescan_queue', ['soft_id' => null]);
		$this->alterColumn('comps_rescan_queue', 'soft_id', $this->integer()->notNull());
	}
}
