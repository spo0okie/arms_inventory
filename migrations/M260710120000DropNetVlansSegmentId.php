<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Удаляет колонку net_vlans.segment_id.
 *
 * Колонка должна была быть удалена ещё в m210310_174301_move_vlans_link (данные
 * перенесены в networks.segments_id), но та миграция проверяла существование
 * колонки 'segments_id' (опечатка — с "s") вместо реальной 'segment_id',
 * поэтому dropColumn молча не выполнялся. На части БД колонка так и осталась
 * висеть без модели (NetVlans её не объявляет), что ломает генерацию
 * swagger-схемы (не удаётся определить тип атрибута NetVlans->segment_id).
 */
class M260710120000DropNetVlansSegmentId extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->dropColumnIfExists('net_vlans', 'segment_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumnIfNotExists('net_vlans', 'segment_id', $this->integer()->null());
    }
}
