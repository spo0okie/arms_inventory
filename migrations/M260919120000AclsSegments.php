<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Сегмент инфраструктуры в списках доступа (plans/access-chains.md, итерация 4; issue #220):
 *  - acls.segments_id — сегмент как РЕСУРС ACL (доступ ко всем сетям и сервисам сегмента),
 *    зеркало networks_id;
 *  - segments_in_aces — сегмент как СУБЪЕКТ ACE (доступ из всех сетей/сервисов сегмента),
 *    зеркало networks_in_aces.
 * Вместе дают матрицу межсегментного доступа: сегменты × сегменты.
 *
 * Имя колонки segments_id — по образцу соседних ресурсных колонок ACL
 * (comps_id/techs_id/ips_id/networks_id/services_id) и пары *_id ↔ *_ids групповой формы.
 * Без FK (конвенция проекта); журналы получают зеркальные колонки.
 * Архивный сегмент делает доступ к нему (и от него) архивным — как архивная сеть.
 */
class M260919120000AclsSegments extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('acls', 'segments_id', $this->integer()->null(), true);
		$this->addColumnIfNotExists('acls_history', 'segments_id', $this->integer()->null());

		//createMany2ManyTable пересоздаёт таблицу — на повторном прогоне существующую не трогаем
		if (!$this->tableExists('segments_in_aces')) {
			$this->createMany2ManyTable('segments_in_aces', ['aces_id', 'segments_id']);
		}
		$this->addColumnIfNotExists('aces_history', 'segments_ids', $this->text());
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('aces_history', 'segments_ids');
		$this->dropTableIfExists('segments_in_aces');
		$this->dropColumnIfExists('acls_history', 'segments_id');
		$this->dropIndexIfExists('idx-acls-segments_id', 'acls');
		$this->dropColumnIfExists('acls', 'segments_id');
	}
}
