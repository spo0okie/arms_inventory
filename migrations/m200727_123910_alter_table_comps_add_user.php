<?php
/**
 * Добавляем
 */
namespace app\migrations;
use yii\db\Migration;

/**
 * Class m200727_123910_alter_table_comps_add_user
 */
class m200727_123910_alter_table_comps_add_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$table=$this->db->getTableSchema('comps');
	
		if (!isset($table->columns['user_id'])) {
			$this->addColumn('comps','user_id',$this->integer()->null());
			$this->createIndex('idx-comps_user','comps', 'user_id');
			// add foreign key for table `{{%department}}`
			$this->addForeignKey(
				'fk-comps_user',
				'comps',
				'user_id',
				'users',
				'id',
				'RESTRICT'
			);
		}
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$table=$this->db->getTableSchema('comps');
		if (isset($table->foreignKeys['fk-comps_user']))        $this->dropForeignKey('fk-comps_user','comps');
		if (isset($table->columns['user_id']))		  			$this->dropColumn('comps','user_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200727_123910_alter_table_comps_add_user cannot be reverted.\n";

        return false;
    }
    */
}
