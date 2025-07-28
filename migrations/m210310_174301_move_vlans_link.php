<?php
namespace app\migrations;
use app\models\Networks;
use yii\db\Migration;

/**
 * Class m210310_174301_move_vlans_link
 */
class m210310_174301_move_vlans_link extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('networks');
		if (!isset($table->columns['segments_id'])) {
			$this->addColumn('networks', 'segments_id', $this->integer()->null());
			$this->createIndex('{{%idx-networks-segments_id}}', '{{%networks}}', '[[segments_id]]');
			$this->addForeignKey(
				'fk-networks-segments_id',
				'networks',
				'segments_id',
				'segments',
				'id',
				'SET NULL'
			);
			
			foreach (Networks::find()->all() as $network) {
				/** @var Networks $network */
				if (is_object($network->netVlan)) {
					$network->segments_id = $network->netVlan->segment_id;
					$network->save();
				}
			}
		}
		
		$table = $this->db->getTableSchema('net_vlans');
		if (isset($table->columns['segments_id'])) {
			$this->dropColumn('net_vlans', 'segment_id');
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('net_vlans');
		if (!isset($table->columns['segments_id'])) {
			$this->addColumn('net_vlans', 'segments_id', $this->integer()->null());
			$this->createIndex('{{%idx-net_vlans-segments_id}}', '{{%net_vlans}}', '[[segments_id]]');
			
			foreach (Networks::find()->all() as $network) {
				/** @var Networks $network */
				if (is_object($network->netVlan)) {
					$network->netVlan->segment_id = $network->segments_id;
					$network->netVlan->save();
				}
			}
		}
		
		$table = $this->db->getTableSchema('networks');
		if (isset($table->columns['segments_id'])) {
			$this->dropForeignKey('fk-networks-segments_id', 'networks');
			$this->dropColumn('networks', 'segments_id');
		}
	}
	
	/*
	// Use up()/down() to run migration code without a transaction.
	public function up()
	{

	}

	public function down()
	{
		echo "m210310_174301_move_vlans_link cannot be reverted.\n";

		return false;
	}
	*/
}
