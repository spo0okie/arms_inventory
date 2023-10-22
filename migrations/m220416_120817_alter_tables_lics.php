<?php
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m220416_120817_alter_tables_lics
 */
class m220416_120817_alter_tables_lics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
	{
		if (is_null($table = $this->db->getTableSchema('lic_items_in_comps'))) {
			$this->createTable('lic_items_in_comps', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_items_id]]' => $this->integer(),
				'[[comps_id]]' => $this->integer(),
			]);
		}
		
		if (is_null($table = $this->db->getTableSchema('lic_items_in_users'))) {
			$this->createTable('lic_items_in_users', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_items_id]]' => $this->integer(),
				'[[users_id]]' => $this->integer(),
			]);
		}
		
		if (is_null($table = $this->db->getTableSchema('lic_groups_in_comps'))) {
			$this->createTable('lic_groups_in_comps', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_groups_id]]' => $this->integer(),
				'[[comps_id]]' => $this->integer(),
			]);
		}
		
		if (is_null($table = $this->db->getTableSchema('lic_groups_in_users'))) {
			$this->createTable('lic_groups_in_users', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_groups_id]]' => $this->integer(),
				'[[users_id]]' => $this->integer(),
			]);
		}
		
		if (is_null($table = $this->db->getTableSchema('lic_keys_in_comps'))) {
			$this->createTable('lic_keys_in_comps', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_keys_id]]' => $this->integer(),
				'[[comps_id]]' => $this->integer(),
			]);
		}
		
		if (is_null($table = $this->db->getTableSchema('lic_keys_in_users'))) {
			$this->createTable('lic_keys_in_users', [
				'[[id]]' => $this->primaryKey(),
				'[[lic_keys_id]]' => $this->integer(),
				'[[users_id]]' => $this->integer(),
			]);
		}
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		if (!is_null($this->db->getTableSchema('lic_items_in_comps')))
			$this->dropTable('lic_items_in_comps');
		
	
		if (!is_null($this->db->getTableSchema('lic_items_in_users')))
			$this->dropTable('lic_items_in_users');
		
	
		if (!is_null($this->db->getTableSchema('lic_groups_in_comps')))
			$this->dropTable('lic_groups_in_comps');
		
	
		if (!is_null($this->db->getTableSchema('lic_groups_in_users')))
			$this->dropTable('lic_groups_in_users');
		
	
		if (!is_null($this->db->getTableSchema('lic_keys_in_comps')))
			$this->dropTable('lic_keys_in_comps');
		
	
		if (!is_null($this->db->getTableSchema('lic_keys_in_users')))
			$this->dropTable('lic_keys_in_users');
		
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220416_120817_alter_tables_lics cannot be reverted.\n";

        return false;
    }
    */
}
