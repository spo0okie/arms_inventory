<?php
namespace app\migrations;
use app\models\NetIps;
use yii\db\Migration;

/**
 * Class m210302_161545_alter_table_net_ips
 */
class m210302_161545_alter_table_net_ips extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('net_ips');
		if (!isset($table->columns['networks_id'])) {
			$this->addColumn('net_ips', 'networks_id', $this->integer()->null());
			$this->createIndex('{{%idx-net_ips-networks_id}}', '{{%net_ips}}', '[[networks_id]]');
			$this->addForeignKey(
				'fk-net_ips-networks_id',
				'net_ips',
				'networks_id',
				'networks',
				'id',
				'SET NULL'
			);
			foreach (NetIps::find()->all() as $ip) $ip->save();
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('net_ips');
		if (isset($table->columns['networks_id'])) {
			$this->dropForeignKey('fk-net_ips-networks_id', 'net_ips');
			$this->dropColumn('net_ips', 'networks_id');
		}
	}
	
}
