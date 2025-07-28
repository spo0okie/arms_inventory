<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m210825_130339_alter_table_scans
 */
class m210825_130339_alter_table_scans extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$table = $this->db->getTableSchema('scans');
		
		if (!isset($table->columns['techs_id'])) {
			$this->addColumn('scans', 'techs_id', $this->integer()->null());
			$this->createIndex('idx-scans_techs_id', 'scans', 'techs_id');
		}
		
		if (!isset($table->columns['arms_id'])) {
			$this->addColumn('scans', 'arms_id', $this->integer()->null());
			$this->createIndex('idx-scans_arms_id', 'scans', 'arms_id');
		}
		
		$table = $this->db->getTableSchema('arms');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('arms', 'scans_id', $this->integer()->null());
		}
		
		$table = $this->db->getTableSchema('techs');
		if (!isset($table->columns['scans_id'])) {
			$this->addColumn('techs', 'scans_id', $this->integer()->null());
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$table = $this->db->getTableSchema('scans');
		if (isset($table->columns['techs_id']))
			$this->dropColumn('scans', 'techs_id');
		
		if (isset($table->columns['arms_id']))
			$this->dropColumn('scans', 'arms_id');
		
		
		$table = $this->db->getTableSchema('arms');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('arms', 'scans_id');
		
		$table = $this->db->getTableSchema('techs');
		if (isset($table->columns['scans_id']))
			$this->dropColumn('techs', 'scans_id');
	}
}
