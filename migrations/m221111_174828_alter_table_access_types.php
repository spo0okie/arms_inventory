<?php

use yii\db\Migration;

/**
 * Class m221111_174828_alter_table_access_types
 */
class m221111_174828_alter_table_access_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumn('access_types', 'is_ip', $this->boolean()->defaultValue(false));
		$this->addColumn('access_types', 'is_phone', $this->boolean()->defaultValue(false));
		$this->addColumn('access_types', 'is_vpn', $this->boolean()->defaultValue(false));
		$this->addColumn('access_types', 'is_app', $this->boolean()->defaultValue(false));
		
		$this->createTable('access_types_hierarchy',[
			'[[id]]'			=> $this->primaryKey(),		        //ключ
			'[[child_id]]'	    => $this->integer()->notNull(),
			'[[parent_id]]'		=> $this->integer()->notNull()
		],'ENGINE=InnoDB');
	
		$this->createIndex('access_types_hiera_parents2children','access_types_hierarchy',['parent_id','child_id']);
		$this->createIndex('access_types_hiera_children2parents','access_types_hierarchy',['child_id','parent_id']);
			
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('access_types', 'is_ip');
		$this->dropColumn('access_types', 'is_phone');
		$this->dropColumn('access_types', 'is_vpn');
		$this->dropColumn('access_types', 'is_app');
		
		$this->dropTable('access_types_hierarchy');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221111_174828_alter_table_access_types cannot be reverted.\n";

        return false;
    }
    */
}
